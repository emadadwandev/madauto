<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ProductMapping;
use App\Services\LoyverseApiService;
use App\Services\ProductMappingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductMappingController extends Controller
{
    protected $productMappingService;

    protected $loyverseApiService;

    public function __construct(ProductMappingService $productMappingService, LoyverseApiService $loyverseApiService)
    {
        $this->productMappingService = $productMappingService;
        $this->loyverseApiService = $loyverseApiService;
    }

    /**
     * Display product mappings list
     */
    public function index(Request $request)
    {
        $query = ProductMapping::query();

        // Filter by platform
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Search by product name or SKU
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('platform_name', 'like', "%{$search}%")
                    ->orWhere('platform_sku', 'like', "%{$search}%")
                    ->orWhere('platform_product_id', 'like', "%{$search}%");
            });
        }

        $mappings = $query->orderBy('platform')->latest()->paginate(20);

        return view('dashboard.product-mappings.index', compact('mappings'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        // Get all Loyverse items for dropdown
        $loyverseItems = $this->productMappingService->getAllLoyverseItemsForMapping();

        return view('dashboard.product-mappings.create', compact('loyverseItems'));
    }

    /**
     * Store new mapping
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|in:careem,talabat',
            'platform_product_id' => 'required|string',
            'platform_sku' => 'nullable|string',
            'platform_name' => 'required|string',
            'loyverse_item_id' => 'required|string',
            'loyverse_variant_id' => 'nullable|string',
        ]);

        // Check for duplicate
        $exists = ProductMapping::where('platform', $validated['platform'])
            ->where('platform_product_id', $validated['platform_product_id'])
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['platform_product_id' => 'This product mapping already exists for the selected platform.']);
        }

        $this->productMappingService->createMapping(
            $validated['platform'],
            $validated['platform_product_id'],
            $validated['platform_name'],
            $validated['loyverse_item_id'],
            $validated['platform_sku'] ?? null,
            $validated['loyverse_variant_id'] ?? null
        );

        return redirect()
            ->route('product-mappings.index')
            ->with('success', 'Product mapping created successfully');
    }

    /**
     * Show edit form
     */
    public function edit(ProductMapping $productMapping)
    {
        $loyverseItems = $this->productMappingService->getAllLoyverseItemsForMapping();

        return view('dashboard.product-mappings.edit', compact('productMapping', 'loyverseItems'));
    }

    /**
     * Update mapping
     */
    public function update(Request $request, ProductMapping $productMapping)
    {
        $validated = $request->validate([
            'platform' => 'required|in:careem,talabat',
            'platform_product_id' => 'required|string',
            'platform_sku' => 'nullable|string',
            'platform_name' => 'required|string',
            'loyverse_item_id' => 'required|string',
            'loyverse_variant_id' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Check for duplicate (excluding current mapping)
        $exists = ProductMapping::where('platform', $validated['platform'])
            ->where('platform_product_id', $validated['platform_product_id'])
            ->where('id', '!=', $productMapping->id)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['platform_product_id' => 'This product mapping already exists for the selected platform.']);
        }

        $productMapping->update($validated);

        // Clear cache
        $this->productMappingService->clearCache();

        return redirect()
            ->route('product-mappings.index')
            ->with('success', 'Product mapping updated successfully');
    }

    /**
     * Delete mapping
     */
    public function destroy(ProductMapping $productMapping)
    {
        $productMapping->delete();

        // Clear cache
        $this->productMappingService->clearCache();

        return redirect()
            ->route('product-mappings.index')
            ->with('success', 'Product mapping deleted successfully');
    }

    /**
     * Toggle mapping status
     */
    public function toggle(ProductMapping $productMapping)
    {
        $productMapping->update(['is_active' => ! $productMapping->is_active]);

        // Clear cache
        $this->productMappingService->clearCache();

        return back()->with('success', 'Product mapping status updated');
    }

    /**
     * Auto-map products by SKU
     */
    public function autoMap()
    {
        try {
            $results = $this->productMappingService->autoMapBySku();

            return back()->with('success', "Auto-mapping completed. Created {$results['created']} mappings, skipped {$results['skipped']}.");
        } catch (\Exception $e) {
            Log::error('Auto-mapping failed: '.$e->getMessage());

            return back()->with('error', 'Auto-mapping failed: '.$e->getMessage());
        }
    }

    /**
     * Import mappings from CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        try {
            $file = $request->file('csv_file');
            $results = $this->productMappingService->importFromCsv($file->getRealPath());

            return back()->with('success', "Import completed. Created {$results['created']} mappings, updated {$results['updated']}, skipped {$results['skipped']}.");
        } catch (\Exception $e) {
            Log::error('Import failed: '.$e->getMessage());

            return back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    /**
     * Export mappings to CSV
     */
    public function export()
    {
        try {
            $csvContent = $this->productMappingService->exportToCsv();

            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="product_mappings_'.date('Y-m-d').'.csv"');
        } catch (\Exception $e) {
            Log::error('Export failed: '.$e->getMessage());

            return back()->with('error', 'Export failed: '.$e->getMessage());
        }
    }

    /**
     * Clear cache
     */
    public function clearCache()
    {
        $this->productMappingService->clearCache();

        return back()->with('success', 'Product mapping cache cleared successfully');
    }
}
