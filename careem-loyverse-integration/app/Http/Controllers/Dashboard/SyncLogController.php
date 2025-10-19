<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use App\Models\Order;
use App\Jobs\SyncToLoyverseJob;
use Illuminate\Http\Request;

class SyncLogController extends Controller
{
    /**
     * Display sync logs list
     */
    public function index(Request $request)
    {
        $query = SyncLog::with('order');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by action (sync type)
        if ($request->filled('sync_type')) {
            $query->where('action', $request->sync_type);
        }

        // Search by order ID
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('order', function($q) use ($search) {
                $q->where('careem_order_id', 'like', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest()->paginate(50);

        // Get statistics
        $stats = [
            'total' => SyncLog::count(),
            'success' => SyncLog::success()->count(),
            'failed' => SyncLog::failed()->count(),
            'today' => SyncLog::whereDate('created_at', today())->count(),
        ];

        return view('dashboard.sync-logs.index', compact('logs', 'stats'));
    }

    /**
     * Show sync log details
     */
    public function show(SyncLog $syncLog)
    {
        $syncLog->load('order.loyverseOrder');

        return view('dashboard.sync-logs.show', compact('syncLog'));
    }

    /**
     * Retry a failed sync
     */
    public function retry($orderId)
    {
        $order = Order::findOrFail($orderId);

        // Update order status back to pending
        $order->update(['status' => 'pending']);

        // Dispatch sync job
        SyncToLoyverseJob::dispatch($order);

        return back()->with('success', 'Order queued for retry. Check sync logs for updates.');
    }

    /**
     * Retry all failed syncs
     */
    public function retryAll()
    {
        $failedOrders = Order::where('status', 'failed')->get();

        foreach ($failedOrders as $order) {
            $order->update(['status' => 'pending']);
            SyncToLoyverseJob::dispatch($order);
        }

        return back()->with('success', "Queued {$failedOrders->count()} failed orders for retry.");
    }
}
