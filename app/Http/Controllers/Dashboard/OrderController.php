<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CareemBranch;
use App\Models\Order;
use App\Services\CareemApiService;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('loyverseOrder')->latest()->paginate(20);

        return view('dashboard.orders.index', compact('orders'));
    }

    /**
     * Fetch orders from Careem API
     */
    public function fetchFromCareem(string $subdomain)
    {
        try {
            $careemService = new CareemApiService(tenant()->id);

            // Get all active branches
            $branches = CareemBranch::where('tenant_id', tenant()->id)
                ->where('pos_integration_enabled', true)
                ->get();

            if ($branches->isEmpty()) {
                return back()->with('warning', 'No active branches found. Please enable POS integration for at least one branch.');
            }

            $totalFetched = 0;
            $errors = [];

            foreach ($branches as $branch) {
                try {
                    if (empty($branch->brand->careem_brand_id)) {
                        $errors[] = "Branch '{$branch->name}' has no brand ID";

                        continue;
                    }

                    if (empty($branch->careem_branch_id)) {
                        $errors[] = "Branch '{$branch->name}' has no branch ID";

                        continue;
                    }

                    Log::info('Fetching orders for branch', [
                        'branch_id' => $branch->id,
                        'branch_name' => $branch->name,
                        'careem_brand_id' => $branch->brand->careem_brand_id,
                        'careem_branch_id' => $branch->careem_branch_id,
                    ]);

                    // Fetch orders from Careem (first page, 20 orders)
                    $response = $careemService->listOrders(
                        $branch->brand->careem_brand_id,
                        $branch->careem_branch_id,
                        1,
                        20
                    );

                    $orders = $response['data'] ?? [];

                    foreach ($orders as $orderSummary) {
                        try {
                            $orderId = $orderSummary['id'] ?? null;

                            if (! $orderId) {
                                Log::warning('Order missing ID, skipping', ['order' => $orderSummary]);

                                continue;
                            }

                            // Fetch FULL order details including modifiers/groups
                            // The listOrders endpoint returns summaries without item details
                            $fullOrderData = $careemService->getOrder(
                                (string) $orderId,
                                $branch->brand->careem_brand_id,
                                $branch->careem_branch_id
                            );

                            // Enrich order data with modifier names from database
                            $enrichmentService = new \App\Services\OrderModifierEnrichmentService;
                            $enrichedOrderData = $enrichmentService->enrichOrderData($fullOrderData, tenant()->id);

                            // Map Careem status to our enum
                            $careemStatus = $enrichedOrderData['status'] ?? 'pending';
                            $ourStatus = match ($careemStatus) {
                                'pending', 'new' => 'pending',
                                'accepted', 'ready', 'picked_up' => 'processing',
                                'delivered', 'completed' => 'synced',
                                'cancelled', 'rejected' => 'failed',
                                default => 'pending',
                            };

                            // Store order with full details (including enriched modifiers)
                            Order::updateOrCreate(
                                [
                                    'tenant_id' => tenant()->id,
                                    'careem_order_id' => $enrichedOrderData['id'],
                                ],
                                [
                                    'platform' => 'careem',
                                    'status' => $ourStatus,
                                    'platform_status' => $careemStatus,
                                    'order_data' => $enrichedOrderData, // Store FULL details with enriched modifiers
                                    'created_at' => $enrichedOrderData['created_at'] ?? now(),
                                ]
                            );
                            $totalFetched++;

                        } catch (\Exception $orderException) {
                            Log::error('Failed to fetch full order details', [
                                'order_id' => $orderId ?? 'unknown',
                                'branch_id' => $branch->id,
                                'error' => $orderException->getMessage(),
                            ]);
                            // Continue with other orders even if one fails
                        }
                    }

                } catch (\Exception $e) {
                    $errors[] = "Branch '{$branch->name}': {$e->getMessage()}";
                    Log::error('Failed to fetch orders for branch', [
                        'branch_id' => $branch->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $message = "Fetched {$totalFetched} order(s) from Careem";

            if (! empty($errors)) {
                $message .= ' | Errors: '.implode('; ', $errors);

                return back()->with('warning', $message);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to fetch orders from Careem', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to fetch orders: '.$e->getMessage());
        }
    }

    /**
     * Show order details
     */
    public function show(string $subdomain, string $order)
    {
        $order = Order::with('loyverseOrder')->findOrFail($order);

        return view('dashboard.orders.show', compact('order'));
    }

    /**
     * Accept order in Careem and sync to Loyverse
     */
    public function accept(string $subdomain, string $order)
    {
        try {
            $order = Order::findOrFail($order);

            if ($order->status === 'processing' || $order->status === 'synced') {
                return back()->with('warning', 'Order is already accepted.');
            }

            if ($order->status === 'cancelled') {
                return back()->with('error', 'Cannot accept a cancelled order.');
            }

            // Get branch information from order data
            $orderData = is_string($order->order_data) ? json_decode($order->order_data, true) : $order->order_data;
            $orderBranchId = $orderData['branch']['id'] ?? null;

            if (! $orderBranchId) {
                return back()->with('error', 'Order does not have branch information.');
            }

            // Find the branch that matches the order's branch
            $branch = CareemBranch::where('tenant_id', tenant()->id)
                ->where('careem_branch_id', $orderBranchId)
                ->first();

            if (! $branch) {
                return back()->with('error', 'Branch not found for this order. Branch ID: '.$orderBranchId);
            }

            if (! $branch->brand || ! $branch->brand->careem_brand_id) {
                return back()->with('error', 'Branch does not have a brand associated.');
            }

            $careemService = new CareemApiService(tenant()->id);

            // Accept order in Careem
            $careemService->acceptOrder(
                $order->careem_order_id,
                $branch->brand->careem_brand_id,
                $branch->careem_branch_id
            );

            // Update local status
            $order->update([
                'status' => 'processing',
                'platform_status' => 'accepted',
                'platform_status_updated_at' => now(),
            ]);

            // Dispatch job to sync to Loyverse
            \App\Jobs\SyncToLoyverseJob::dispatch($order)->onQueue('high');

            return back()->with('success', 'Order accepted successfully! Syncing to Loyverse...');

        } catch (\Exception $e) {
            Log::error('Failed to accept order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to accept order: '.$e->getMessage());
        }
    }

    /**
     * Mark order as ready in Careem
     */
    public function markReady(string $subdomain, string $order)
    {
        try {
            $order = Order::findOrFail($order);

            if ($order->platform_status === 'ready') {
                return back()->with('warning', 'Order is already marked as ready.');
            }

            if ($order->platform_status === 'cancelled') {
                return back()->with('error', 'Cannot mark a cancelled order as ready.');
            }

            if ($order->status !== 'processing' && $order->status !== 'synced') {
                return back()->with('error', 'Order must be accepted first.');
            }

            // Get branch information from order data
            $orderData = is_string($order->order_data) ? json_decode($order->order_data, true) : $order->order_data;
            $orderBranchId = $orderData['branch']['id'] ?? null;

            if (! $orderBranchId) {
                return back()->with('error', 'Order does not have branch information.');
            }

            // Find the branch that matches the order's branch
            $branch = CareemBranch::where('tenant_id', tenant()->id)
                ->where('careem_branch_id', $orderBranchId)
                ->first();

            if (! $branch) {
                return back()->with('error', 'Branch not found for this order. Branch ID: '.$orderBranchId);
            }

            if (! $branch->brand || ! $branch->brand->careem_brand_id) {
                return back()->with('error', 'Branch does not have a brand associated.');
            }

            $careemService = new CareemApiService(tenant()->id);

            // First, fetch current order state from Careem to verify it's accepted
            try {
                $currentOrderData = $careemService->getOrder(
                    $order->careem_order_id,
                    $branch->brand->careem_brand_id,
                    $branch->careem_branch_id
                );

                Log::info('Fetched current order state from Careem before marking ready', [
                    'order_id' => $order->id,
                    'careem_order_id' => $order->careem_order_id,
                    'careem_status' => $currentOrderData['status'] ?? 'unknown',
                    'local_platform_status' => $order->platform_status,
                ]);

                // Verify order is in accepted state on Careem's side
                if (($currentOrderData['status'] ?? null) !== 'accepted') {
                    return back()->with('error', 'Order is not in "accepted" state on Careem. Current state: '.($currentOrderData['status'] ?? 'unknown').'. Please wait a few moments and try again.');
                }
            } catch (\Exception $e) {
                Log::warning('Could not fetch order state from Careem before marking ready', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue anyway - don't block the operation
            }

            // Log the request details
            Log::info('Attempting to mark order ready', [
                'order_id' => $order->id,
                'careem_order_id' => $order->careem_order_id,
                'current_status' => $order->status,
                'current_platform_status' => $order->platform_status,
                'brand_id' => $branch->brand->careem_brand_id,
                'branch_id' => $branch->careem_branch_id,
            ]);

            // Mark order ready in Careem
            $careemService->markOrderReady(
                $order->careem_order_id,
                $branch->brand->careem_brand_id,
                $branch->careem_branch_id
            );

            // Update local status (keep status as is, only update platform_status)
            $order->update([
                'platform_status' => 'ready',
                'platform_status_updated_at' => now(),
            ]);

            return back()->with('success', 'Order marked as ready successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to mark order ready', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            $errorMsg = $e->getMessage();

            // Provide helpful context for common errors
            if (str_contains($errorMsg, 'BADREQUEST_ERROR') || str_contains($errorMsg, 'bad request')) {
                $errorMsg .= ' | Note: This might be a Careem API staging environment limitation or your account/branch might not have the "ready" state enabled. Contact Careem support if this persists.';
            }

            return back()->with('error', 'Failed to mark order ready: '.$errorMsg);
        }
    }

    /**
     * Cancel order in Careem
     */
    public function cancel(string $subdomain, string $order)
    {
        try {
            $order = Order::findOrFail($order);

            if ($order->platform_status === 'cancelled') {
                return back()->with('warning', 'Order is already cancelled.');
            }

            // Get branch information from order data
            $orderData = is_string($order->order_data) ? json_decode($order->order_data, true) : $order->order_data;
            $orderBranchId = $orderData['branch']['id'] ?? null;

            if (! $orderBranchId) {
                return back()->with('error', 'Order does not have branch information.');
            }

            // Find the branch that matches the order's branch
            $branch = CareemBranch::where('tenant_id', tenant()->id)
                ->where('careem_branch_id', $orderBranchId)
                ->first();

            if (! $branch) {
                return back()->with('error', 'Branch not found for this order. Branch ID: '.$orderBranchId);
            }

            if (! $branch->brand || ! $branch->brand->careem_brand_id) {
                return back()->with('error', 'Branch does not have a brand associated.');
            }

            $careemService = new CareemApiService(tenant()->id);

            // Cancel order in Careem with a default reason
            $careemService->cancelOrder(
                $order->careem_order_id,
                $branch->brand->careem_brand_id,
                $branch->careem_branch_id,
                'OTHER'  // Default cancellation reason
            );

            // Update local status
            $order->update([
                'status' => 'failed',
                'platform_status' => 'cancelled',
                'platform_status_updated_at' => now(),
            ]);

            return back()->with('success', 'Order cancelled successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to cancel order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to cancel order: '.$e->getMessage());
        }
    }
}
