<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CareemBrand;
use App\Services\CareemApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CareemBrandController extends Controller
{
    /**
     * Display a listing of Careem brands
     */
    public function index(Request $request, string $subdomain)
    {
        $brands = CareemBrand::withCount('branches')
            ->latest()
            ->paginate(12);

        return view('dashboard.careem-brands.index', compact('brands'));
    }

    /**
     * Show the form for creating a new brand
     */
    public function create(string $subdomain)
    {
        return view('dashboard.careem-brands.create');
    }

    /**
     * Store a newly created brand
     */
    public function store(Request $request, string $subdomain)
    {
        $validated = $request->validate([
            'careem_brand_id' => 'required|string|max:255|unique:careem_brands,careem_brand_id',
            'name' => 'required|string|max:255',
            'sync_to_careem' => 'nullable|boolean',
        ]);

        try {
            $brand = CareemBrand::create([
                'tenant_id' => tenant()->id,
                'careem_brand_id' => $validated['careem_brand_id'],
                'name' => $validated['name'],
                'state' => 'UNMAPPED',
            ]);

            // Sync to Careem API if requested
            if ($request->boolean('sync_to_careem')) {
                try {
                    $careemService = new CareemApiService(tenant()->id);
                    $response = $careemService->createBrand(
                        $brand->careem_brand_id,
                        $brand->name
                    );

                    // Update brand with Careem response
                    $brand->update([
                        'state' => $response['state'] ?? 'UNMAPPED',
                        'metadata' => $response,
                        'synced_at' => now(),
                    ]);

                    return redirect()
                        ->route('tenant.careem-brands.index', $subdomain)
                        ->with('success', 'Brand created and synced to Careem successfully!');
                } catch (\Exception $e) {
                    Log::error('Failed to sync brand to Careem', [
                        'brand_id' => $brand->id,
                        'error' => $e->getMessage(),
                    ]);

                    return redirect()
                        ->route('tenant.careem-brands.index', $subdomain)
                        ->with('warning', 'Brand created locally but failed to sync to Careem: ' . $e->getMessage());
                }
            }

            return redirect()
                ->route('tenant.careem-brands.index', $subdomain)
                ->with('success', 'Brand created successfully! Remember to sync it to Careem.');
        } catch (\Exception $e) {
            Log::error('Failed to create brand', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create brand: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified brand
     */
    public function edit(string $subdomain, $careemBrand)
    {
        $brand = CareemBrand::findOrFail($careemBrand);

        return view('dashboard.careem-brands.edit', compact('brand'));
    }

    /**
     * Update the specified brand
     */
    public function update(Request $request, string $subdomain, $careemBrand)
    {
        $brand = CareemBrand::findOrFail($careemBrand);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sync_to_careem' => 'nullable|boolean',
        ]);

        try {
            $brand->update([
                'name' => $validated['name'],
            ]);

            // Sync to Careem API if requested
            if ($request->boolean('sync_to_careem')) {
                try {
                    $careemService = new CareemApiService(tenant()->id);
                    $response = $careemService->updateBrand(
                        $brand->careem_brand_id,
                        $brand->name
                    );

                    $brand->update([
                        'state' => $response['state'] ?? $brand->state,
                        'metadata' => $response,
                        'synced_at' => now(),
                    ]);

                    return redirect()
                        ->route('tenant.careem-brands.index', $subdomain)
                        ->with('success', 'Brand updated and synced to Careem successfully!');
                } catch (\Exception $e) {
                    Log::error('Failed to sync brand to Careem', [
                        'brand_id' => $brand->id,
                        'error' => $e->getMessage(),
                    ]);

                    return redirect()
                        ->route('tenant.careem-brands.index', $subdomain)
                        ->with('warning', 'Brand updated locally but failed to sync to Careem: ' . $e->getMessage());
                }
            }

            return redirect()
                ->route('tenant.careem-brands.index', $subdomain)
                ->with('success', 'Brand updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to update brand', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update brand: ' . $e->getMessage());
        }
    }

    /**
     * Sync brand to Careem
     */
    public function sync(string $subdomain, $careemBrand)
    {
        $brand = CareemBrand::findOrFail($careemBrand);
        try {
            $careemService = new CareemApiService(tenant()->id);

            // Check if brand has careem_brand_id
            if (empty($brand->careem_brand_id)) {
                return redirect()
                    ->route('dashboard.careem-brands.index', ['subdomain' => $subdomain])
                    ->with('error', 'Brand must have a valid Brand ID before syncing to Careem.');
            }

            // Try to fetch brand first to see if it exists
            try {
                $response = $careemService->getBrand($brand->careem_brand_id);

                // Brand exists, update it
                $response = $careemService->updateBrand(
                    $brand->careem_brand_id,
                    $brand->name
                );
            } catch (\Exception $e) {
                // Brand doesn't exist, create it
                $response = $careemService->createBrand(
                    $brand->careem_brand_id,
                    $brand->name
                );
            }

            $brand->update([
                'state' => $response['state'] ?? 'UNMAPPED',
                'metadata' => $response,
                'synced_at' => now(),
            ]);

            return back()->with('success', 'Brand synced to Careem successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to sync brand to Careem', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to sync brand: ' . $e->getMessage());
        }
    }

    /**
     * Fetch brand details from Careem
     */
    public function fetchFromCareem(string $subdomain, $careemBrand)
    {
        $brand = CareemBrand::findOrFail($careemBrand);
        try {
            // Check if brand has careem_brand_id
            if (empty($brand->careem_brand_id)) {
                return back()->with('error', 'Brand must have a valid Brand ID before fetching from Careem.');
            }

            $careemService = new CareemApiService(tenant()->id);
            $response = $careemService->getBrand($brand->careem_brand_id);

            $brand->update([
                'name' => $response['name'] ?? $brand->name,
                'state' => $response['state'] ?? $brand->state,
                'metadata' => $response,
                'synced_at' => now(),
            ]);

            return back()->with('success', 'Brand details fetched from Careem successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to fetch brand from Careem', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to fetch brand: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified brand
     */
    public function destroy(string $subdomain, $careemBrand)
    {
        $brand = CareemBrand::findOrFail($careemBrand);
        try {
            // Check if brand has branches
            if ($brand->branches()->count() > 0) {
                return back()->with('error', 'Cannot delete brand with existing branches. Delete branches first.');
            }

            $brand->delete();

            return redirect()
                ->route('tenant.careem-brands.index', $subdomain)
                ->with('success', 'Brand deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to delete brand', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete brand: ' . $e->getMessage());
        }
    }

    /**
     * Delete brand from Careem
     */
    public function deleteFromCareem(string $subdomain, $careemBrand)
    {
        $brand = CareemBrand::findOrFail($careemBrand);
        try {
            // Check if brand has careem_brand_id
            if (empty($brand->careem_brand_id)) {
                return back()->with('error', 'Brand must have a valid Brand ID before deleting from Careem.');
            }

            $careemService = new CareemApiService(tenant()->id);
            $careemService->deleteBrand($brand->careem_brand_id);

            $brand->update([
                'state' => 'UNMAPPED',
                'synced_at' => now(),
            ]);

            return back()->with('success', 'Brand deleted from Careem successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to delete brand from Careem', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete from Careem: ' . $e->getMessage());
        }
    }
}
