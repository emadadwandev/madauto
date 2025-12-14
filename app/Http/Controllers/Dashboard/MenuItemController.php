<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\ModifierGroup;
use App\Services\LoyverseApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
    protected $loyverseApiService;

    public function __construct(LoyverseApiService $loyverseApiService)
    {
        $this->loyverseApiService = $loyverseApiService;
    }

    /**
     * Show the form for creating a new menu item.
     */
    public function create(string $subdomain, Menu $menu)
    {
        $modifierGroups = ModifierGroup::active()->with('activeModifiers')->orderBy('sort_order')->get();

        // Get Loyverse items for mapping (filtered by careem/talabat categories)
        $loyverseItems = [];
        try {
            if ($this->loyverseApiService->hasCredentials()) {
                $loyverseItems = $this->loyverseApiService->getAllItems(false, ['careem', 'talabat']);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Loyverse items', ['error' => $e->getMessage()]);
        }

        return view('dashboard.menu-items.create', compact('menu', 'modifierGroups', 'loyverseItems'));
    }

    /**
     * Store a newly created menu item.
     */
    public function store(Request $request, string $subdomain, Menu $menu)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'sku' => 'nullable|string|max:255',
            'default_quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'loyverse_item_id' => 'nullable|string',
            'loyverse_variant_id' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
            'modifier_groups' => 'nullable|array',
            'modifier_groups.*' => 'exists:modifier_groups,id',
        ]);

        DB::beginTransaction();

        try {
            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('menu-items', 'public');
            }

            // Create menu item
            $menuItem = MenuItem::create([
                'menu_id' => $menu->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'image_url' => $imagePath,
                'sku' => $validated['sku'] ?? null,
                'default_quantity' => $validated['default_quantity'],
                'price' => $validated['price'],
                'tax_rate' => $validated['tax_rate'],
                'loyverse_item_id' => $validated['loyverse_item_id'] ?? null,
                'loyverse_variant_id' => $validated['loyverse_variant_id'] ?? null,
                'category' => $validated['category'] ?? null,
                'is_available' => $request->has('is_available'),
                'is_active' => $request->has('is_active'),
                'sort_order' => $menu->items()->max('sort_order') + 1,
            ]);

            // Attach modifier groups
            if (isset($validated['modifier_groups'])) {
                $syncData = [];
                foreach ($validated['modifier_groups'] as $index => $groupId) {
                    $syncData[$groupId] = [
                        'sort_order' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $menuItem->modifierGroups()->sync($syncData);
            }

            // Auto-create product mappings for platforms if Loyverse item is selected
            if (!empty($validated['loyverse_item_id'])) {
                $platforms = ['careem', 'talabat'];
                foreach ($platforms as $platform) {
                    // Check if mapping already exists
                    $existingMapping = \App\Models\ProductMapping::where('platform', $platform)
                        ->where('platform_product_id', $menuItem->id)
                        ->first();

                    if (!$existingMapping) {
                        \App\Models\ProductMapping::create([
                            'tenant_id' => tenant()->id,
                            'platform' => $platform,
                            'platform_product_id' => (string) $menuItem->id,
                            'platform_sku' => $validated['sku'] ?? null,
                            'platform_name' => $validated['name'],
                            'loyverse_item_id' => $validated['loyverse_item_id'],
                            'loyverse_variant_id' => $validated['loyverse_variant_id'] ?? null,
                            'is_active' => true,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('dashboard.menus.edit', ['menu' => $menu, 'subdomain' => request()->route('subdomain')])
                ->with('success', 'Menu item added successfully and product mappings created.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded image if exists
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            Log::error('Failed to create menu item', [
                'menu_id' => $menu->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to create menu item: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the menu item.
     */
    public function edit(string $subdomain, Menu $menu, MenuItem $menuItem)
    {
        $menuItem->load('modifierGroups');
        $modifierGroups = ModifierGroup::active()->with('activeModifiers')->orderBy('sort_order')->get();

        // Get Loyverse items for mapping (filtered by careem/talabat categories)
        $loyverseItems = [];
        try {
            if ($this->loyverseApiService->hasCredentials()) {
                $loyverseItems = $this->loyverseApiService->getAllItems(false, ['careem', 'talabat']);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Loyverse items', ['error' => $e->getMessage()]);
        }

        return view('dashboard.menu-items.edit', compact('menu', 'menuItem', 'modifierGroups', 'loyverseItems'));
    }

    /**
     * Update the specified menu item.
     */
    public function update(Request $request, string $subdomain, Menu $menu, MenuItem $menuItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_image' => 'boolean',
            'sku' => 'nullable|string|max:255',
            'default_quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'loyverse_item_id' => 'nullable|string',
            'loyverse_variant_id' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
            'modifier_groups' => 'nullable|array',
            'modifier_groups.*' => 'exists:modifier_groups,id',
        ]);

        DB::beginTransaction();

        try {
            // Handle image removal
            if ($request->has('remove_image') && $menuItem->image_url) {
                Storage::disk('public')->delete($menuItem->image_url);
                $menuItem->image_url = null;
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image
                if ($menuItem->image_url) {
                    Storage::disk('public')->delete($menuItem->image_url);
                }
                $menuItem->image_url = $request->file('image')->store('menu-items', 'public');
            }

            // Update menu item
            $menuItem->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sku' => $validated['sku'] ?? null,
                'default_quantity' => $validated['default_quantity'],
                'price' => $validated['price'],
                'tax_rate' => $validated['tax_rate'],
                'loyverse_item_id' => $validated['loyverse_item_id'] ?? null,
                'loyverse_variant_id' => $validated['loyverse_variant_id'] ?? null,
                'category' => $validated['category'] ?? null,
                'is_available' => $request->has('is_available'),
                'is_active' => $request->has('is_active'),
            ]);

            // Sync modifier groups
            if (isset($validated['modifier_groups'])) {
                $syncData = [];
                foreach ($validated['modifier_groups'] as $index => $groupId) {
                    $syncData[$groupId] = [
                        'sort_order' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $menuItem->modifierGroups()->sync($syncData);
            } else {
                $menuItem->modifierGroups()->detach();
            }

            // Update product mappings for platforms if Loyverse item changed
            if (!empty($validated['loyverse_item_id'])) {
                $platforms = ['careem', 'talabat'];
                foreach ($platforms as $platform) {
                    \App\Models\ProductMapping::updateOrCreate(
                        [
                            'platform' => $platform,
                            'platform_product_id' => (string) $menuItem->id,
                        ],
                        [
                            'tenant_id' => tenant()->id,
                            'platform_sku' => $validated['sku'] ?? null,
                            'platform_name' => $validated['name'],
                            'loyverse_item_id' => $validated['loyverse_item_id'],
                            'loyverse_variant_id' => $validated['loyverse_variant_id'] ?? null,
                            'is_active' => true,
                        ]
                    );
                }
            } else {
                // If Loyverse item removed, deactivate mappings
                \App\Models\ProductMapping::where('platform_product_id', (string) $menuItem->id)
                    ->update(['is_active' => false]);
            }

            DB::commit();

            return redirect()->route('dashboard.menus.edit', ['menu' => $menu, 'subdomain' => request()->route('subdomain')])
                ->with('success', 'Menu item and product mappings updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update menu item', [
                'menu_item_id' => $menuItem->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to update menu item: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified menu item.
     */
    public function destroy(string $subdomain, Menu $menu, MenuItem $menuItem)
    {
        DB::beginTransaction();

        try {
            // Delete image if exists
            if ($menuItem->image_url) {
                Storage::disk('public')->delete($menuItem->image_url);
            }

            $menuItem->delete();

            DB::commit();

            return redirect()->route('dashboard.menus.edit', ['menu' => $menu, 'subdomain' => request()->route('subdomain')])
                ->with('success', 'Menu item deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete menu item', [
                'menu_item_id' => $menuItem->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete menu item: '.$e->getMessage());
        }
    }

    /**
     * Toggle menu item availability.
     */
    public function toggleAvailability(string $subdomain, Menu $menu, MenuItem $menuItem)
    {
        $menuItem->is_available = ! $menuItem->is_available;
        $menuItem->save();

        $status = $menuItem->is_available ? 'available' : 'unavailable';

        return back()->with('success', "Menu item marked as {$status}.");
    }

    /**
     * Reorder menu items.
     */
    public function reorder(Request $request, string $subdomain, Menu $menu)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:menu_items,id',
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->order as $index => $id) {
                MenuItem::where('id', $id)
                    ->where('menu_id', $menu->id)
                    ->update(['sort_order' => $index]);
            }

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to reorder menu items', [
                'menu_id' => $menu->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Duplicate a menu item.
     */
    public function duplicate(string $subdomain, Menu $menu, MenuItem $menuItem)
    {
        DB::beginTransaction();

        try {
            $newItem = $menuItem->replicate();
            $newItem->name = $menuItem->name.' (Copy)';
            $newItem->sort_order = $menu->items()->max('sort_order') + 1;
            $newItem->save();

            // Duplicate modifier group assignments
            foreach ($menuItem->modifierGroups as $modifierGroup) {
                $newItem->modifierGroups()->attach($modifierGroup->id, [
                    'sort_order' => $modifierGroup->pivot->sort_order,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('dashboard.menus.edit', ['menu' => $menu, 'subdomain' => request()->route('subdomain')])
                ->with('success', 'Menu item duplicated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to duplicate menu item', [
                'menu_item_id' => $menuItem->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to duplicate menu item: '.$e->getMessage());
        }
    }
}
