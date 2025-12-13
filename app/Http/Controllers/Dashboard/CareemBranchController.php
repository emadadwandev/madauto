<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CareemBrand;
use App\Models\CareemBranch;
use App\Models\Location;
use App\Services\CareemApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CareemBranchController extends Controller
{
    /**
     * Display a listing of Careem branches
     */
    public function index(Request $request, string $subdomain)
    {
        $query = CareemBranch::with(['brand', 'location']);

        // Filter by brand if specified
        if ($request->filled('brand_id')) {
            $query->where('careem_brand_id', $request->brand_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('state', $request->status);
        }

        $branches = $query->latest()->paginate(12);
        $brands = CareemBrand::orderBy('name')->get();

        return view('dashboard.careem-branches.index', compact('branches', 'brands'));
    }

    /**
     * Show the form for creating a new branch
     */
    public function create(string $subdomain)
    {
        $brands = CareemBrand::orderBy('name')->get();
        $locations = Location::whereDoesntHave('careemBranch')->orderBy('name')->get();

        return view('dashboard.careem-branches.create', compact('brands', 'locations'));
    }

    /**
     * Store a newly created branch
     */
    public function store(Request $request, string $subdomain)
    {
        $validated = $request->validate([
            'careem_brand_id' => 'required|exists:careem_brands,id',
            'careem_branch_id' => 'required|string|max:255|unique:careem_branches,careem_branch_id',
            'name' => 'required|string|max:255',
            'location_id' => 'nullable|exists:locations,id',
            'sync_to_careem' => 'nullable|boolean',
        ]);

        try {
            $brand = CareemBrand::findOrFail($validated['careem_brand_id']);

            $branch = CareemBranch::create([
                'tenant_id' => tenant()->id,
                'careem_brand_id' => $brand->id,
                'careem_branch_id' => $validated['careem_branch_id'],
                'name' => $validated['name'],
                'location_id' => $validated['location_id'] ?? null,
                'state' => 'UNMAPPED',
                'pos_integration_enabled' => false,
                'visibility_status' => 2, // Inactive by default
            ]);

            // Sync to Careem API if requested
            if ($request->boolean('sync_to_careem')) {
                try {
                    $careemService = new CareemApiService(tenant()->id);
                    $response = $careemService->createOrUpdateBranch(
                        $brand->careem_brand_id,
                        $branch->careem_branch_id,
                        $branch->name
                    );

                    $branch->update([
                        'state' => $response['state'] ?? 'UNMAPPED',
                        'metadata' => $response,
                        'synced_at' => now(),
                    ]);

                    return redirect()
                        ->route('tenant.careem-branches.index', $subdomain)
                        ->with('success', 'Branch created and synced to Careem successfully! Note: Ask Careem operations team to map this branch to an outlet.');
                } catch (\Exception $e) {
                    Log::error('Failed to sync branch to Careem', [
                        'branch_id' => $branch->id,
                        'error' => $e->getMessage(),
                    ]);

                    return redirect()
                        ->route('tenant.careem-branches.index', $subdomain)
                        ->with('warning', 'Branch created locally but failed to sync to Careem: ' . $e->getMessage());
                }
            }

            return redirect()
                ->route('tenant.careem-branches.index', $subdomain)
                ->with('success', 'Branch created successfully! Remember to sync it to Careem and request mapping from operations team.');
        } catch (\Exception $e) {
            Log::error('Failed to create branch', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create branch: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified branch
     */
    public function edit(string $subdomain, $careemBranch)
    {
        $branch = CareemBranch::findOrFail($careemBranch);
        $brands = CareemBrand::orderBy('name')->get();
        $locations = Location::whereDoesntHave('careemBranch')
            ->orWhere('id', $branch->location_id)
            ->orderBy('name')
            ->get();

        return view('dashboard.careem-branches.edit', compact('branch', 'brands', 'locations'));
    }

    /**
     * Update the specified branch
     */
    public function update(Request $request, string $subdomain, $careemBranch)
    {
        $branch = CareemBranch::findOrFail($careemBranch);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location_id' => 'nullable|exists:locations,id',
            'sync_to_careem' => 'nullable|boolean',
        ]);

        try {
            $branch->update([
                'name' => $validated['name'],
                'location_id' => $validated['location_id'] ?? null,
            ]);

            // Sync to Careem API if requested
            if ($request->boolean('sync_to_careem')) {
                try {
                    $careemService = new CareemApiService(tenant()->id);
                    $response = $careemService->createOrUpdateBranch(
                        $branch->brand->careem_brand_id,
                        $branch->careem_branch_id,
                        $branch->name
                    );

                    $branch->update([
                        'state' => $response['state'] ?? $branch->state,
                        'metadata' => $response,
                        'synced_at' => now(),
                    ]);

                    return redirect()
                        ->route('tenant.careem-branches.index', $subdomain)
                        ->with('success', 'Branch updated and synced to Careem successfully!');
                } catch (\Exception $e) {
                    Log::error('Failed to sync branch to Careem', [
                        'branch_id' => $branch->id,
                        'error' => $e->getMessage(),
                    ]);

                    return redirect()
                        ->route('tenant.careem-branches.index', $subdomain)
                        ->with('warning', 'Branch updated locally but failed to sync to Careem: ' . $e->getMessage());
                }
            }

            return redirect()
                ->route('tenant.careem-branches.index', $subdomain)
                ->with('success', 'Branch updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to update branch', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update branch: ' . $e->getMessage());
        }
    }

    /**
     * Sync branch to Careem
     */
    public function sync(string $subdomain, $careemBranch)
    {
        $branch = CareemBranch::findOrFail($careemBranch);
        try {
            // Validate required IDs
            if (empty($branch->careem_branch_id)) {
                return back()->with('error', 'Branch must have a valid Branch ID before syncing.');
            }
            if (empty($branch->brand->careem_brand_id)) {
                return back()->with('error', 'Brand must have a valid Brand ID before syncing branches.');
            }

            $careemService = new CareemApiService(tenant()->id);

            // Create or update branch
            $response = $careemService->createOrUpdateBranch(
                $branch->brand->careem_brand_id,
                $branch->careem_branch_id,
                $branch->name
            );

            $branch->update([
                'state' => $response['state'] ?? 'UNMAPPED',
                'metadata' => $response,
                'synced_at' => now(),
            ]);

            return back()->with('success', 'Branch synced to Careem successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to sync branch to Careem', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to sync branch: ' . $e->getMessage());
        }
    }

    /**
     * Fetch branch details from Careem
     */
    public function fetchFromCareem(string $subdomain, $careemBranch)
    {
        $branch = CareemBranch::findOrFail($careemBranch);
        try {
            // Validate required IDs
            if (empty($branch->careem_branch_id)) {
                return back()->with('error', 'Branch must have a valid Branch ID before fetching.');
            }
            if (empty($branch->brand->careem_brand_id)) {
                return back()->with('error', 'Brand must have a valid Brand ID before fetching branches.');
            }

            $careemService = new CareemApiService(tenant()->id);
            $response = $careemService->getBranch(
                $branch->brand->careem_brand_id,
                $branch->careem_branch_id
            );

            $branch->update([
                'name' => $response['name'] ?? $branch->name,
                'state' => $response['state'] ?? $branch->state,
                'metadata' => $response,
                'synced_at' => now(),
            ]);

            return back()->with('success', 'Branch details fetched from Careem successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to fetch branch from Careem', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to fetch branch: ' . $e->getMessage());
        }
    }

    /**
     * Toggle POS integration
     */
    public function togglePosIntegration(string $subdomain, $careemBranch)
    {
        $branch = CareemBranch::findOrFail($careemBranch);
        try {
            // Validate required IDs
            if (empty($branch->careem_branch_id)) {
                return back()->with('error', 'Branch must have a valid Branch ID before toggling POS.');
            }
            if (empty($branch->brand->careem_brand_id)) {
                return back()->with('error', 'Brand must have a valid Brand ID before toggling POS.');
            }

            $newStatus = !$branch->pos_integration_enabled;

            $careemService = new CareemApiService(tenant()->id);
            $response = $careemService->toggleBranchPosIntegration(
                $branch->brand->careem_brand_id,
                $branch->careem_branch_id,
                $newStatus
            );

            $branch->update([
                'pos_integration_enabled' => $newStatus,
                'metadata' => $response,
                'synced_at' => now(),
            ]);

            $status = $newStatus ? 'enabled' : 'disabled';
            return back()->with('success', "POS integration {$status} successfully!");
        } catch (\Exception $e) {
            Log::error('Failed to toggle POS integration', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to toggle POS integration: ' . $e->getMessage());
        }
    }

    /**
     * Update branch visibility status (Active/Inactive on SuperApp)
     */
    public function updateVisibility(Request $request, string $subdomain, $careemBranch)
    {
        $branch = CareemBranch::findOrFail($careemBranch);
        $validated = $request->validate([
            'visibility_status' => 'required|integer|in:1,2',
        ]);

        try {
            // Validate required IDs
            if (empty($branch->careem_branch_id)) {
                return back()->with('error', 'Branch must have a valid Branch ID before updating visibility.');
            }
            if (empty($branch->brand->careem_brand_id)) {
                return back()->with('error', 'Brand must have a valid Brand ID before updating visibility.');
            }

            $careemService = new CareemApiService(tenant()->id);
            $careemService->updateBranchVisibilityStatus(
                $branch->brand->careem_brand_id,
                $branch->careem_branch_id,
                $validated['visibility_status']
            );

            $branch->update([
                'visibility_status' => $validated['visibility_status'],
                'synced_at' => now(),
            ]);

            $status = $validated['visibility_status'] === 1 ? 'Active' : 'Inactive';
            return back()->with('success', "Branch status updated to {$status} successfully!");
        } catch (\Exception $e) {
            Log::error('Failed to update branch visibility', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update visibility: ' . $e->getMessage());
        }
    }

    /**
     * Set temporary status (e.g., close for X minutes)
     */
    public function setTemporaryStatus(Request $request, string $subdomain, $careemBranch)
    {
        $branch = CareemBranch::findOrFail($careemBranch);
        $validated = $request->validate([
            'status_id' => 'required|integer|in:2', // Only inactive supported
            'till_time_minutes' => 'required|integer|min:1|max:1440', // Max 24 hours
        ]);

        try {
            // Validate required IDs
            if (empty($branch->careem_branch_id)) {
                return back()->with('error', 'Branch must have a valid Branch ID before setting temporary status.');
            }
            if (empty($branch->brand->careem_brand_id)) {
                return back()->with('error', 'Brand must have a valid Brand ID before setting temporary status.');
            }

            $careemService = new CareemApiService(tenant()->id);
            $response = $careemService->setBranchStatusExpiry(
                $branch->brand->careem_brand_id,
                $branch->careem_branch_id,
                $validated['status_id'],
                $validated['till_time_minutes']
            );

            return back()->with('success', "Branch will be inactive for {$validated['till_time_minutes']} minutes.");
        } catch (\Exception $e) {
            Log::error('Failed to set temporary status', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to set temporary status: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified branch
     */
    public function destroy(string $subdomain, $careemBranch)
    {
        $branch = CareemBranch::findOrFail($careemBranch);
        try {
            $branch->delete();

            return redirect()
                ->route('tenant.careem-branches.index', $subdomain)
                ->with('success', 'Branch deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to delete branch', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete branch: ' . $e->getMessage());
        }
    }

    /**
     * Delete branch from Careem
     */
    public function deleteFromCareem(string $subdomain, $careemBranch)
    {
        $branch = CareemBranch::findOrFail($careemBranch);
        try {
            // Validate required IDs
            if (empty($branch->careem_branch_id)) {
                return back()->with('error', 'Branch must have a valid Branch ID before deleting from Careem.');
            }
            if (empty($branch->brand->careem_brand_id)) {
                return back()->with('error', 'Brand must have a valid Brand ID before deleting from Careem.');
            }

            $careemService = new CareemApiService(tenant()->id);
            $careemService->deleteBranch(
                $branch->brand->careem_brand_id,
                $branch->careem_branch_id
            );

            $branch->update([
                'state' => 'UNMAPPED',
                'synced_at' => now(),
            ]);

            return back()->with('success', 'Branch deleted from Careem successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to delete branch from Careem', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete from Careem: ' . $e->getMessage());
        }
    }
}
