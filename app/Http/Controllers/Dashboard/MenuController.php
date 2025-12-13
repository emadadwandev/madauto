<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MenuController extends Controller
{
    /**
     * Display a listing of menus.
     */
    public function index(Request $request)
    {
        $query = Menu::with(['items', 'activeLocations']);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'published') {
                $query->published();
            } elseif ($request->status === 'draft') {
                $query->draft();
            }
        }

        // Filter by active
        if ($request->has('active')) {
            $query->where('is_active', $request->active === 'yes');
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $menus = $query->latest()->paginate(12);

        return view('dashboard.menus.index', compact('menus'));
    }

    /**
     * Show the form for creating a new menu.
     */
    public function create()
    {
        $locations = Location::active()->orderBy('name')->get();

        return view('dashboard.menus.create', compact('locations'));
    }

    /**
     * Store a newly created menu.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'is_active' => 'boolean',
            'locations' => 'nullable|array',
            'locations.*' => 'exists:locations,id',
            'platforms' => 'nullable|array',
            'platforms.*' => 'in:careem,talabat',
        ]);

        DB::beginTransaction();

        try {
            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('menus', 'public');
            }

            // Create menu
            $menu = Menu::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'image_url' => $imagePath,
                'status' => 'draft',
                'is_active' => $request->has('is_active'),
            ]);

            // Assign locations
            if (isset($validated['locations'])) {
                $locationData = [];
                foreach ($validated['locations'] as $locationId) {
                    $locationData[$locationId] = [
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $menu->locations()->attach($locationData);
            }

            // Assign platforms
            if (isset($validated['platforms'])) {
                foreach ($validated['platforms'] as $platform) {
                    $menu->assignToPlatform($platform);
                }
            }

            DB::commit();

            // Log activity
            \App\Services\UserActivityService::log('menu.created', null, [
                'menu_id' => $menu->id,
                'menu_name' => $menu->name,
            ]);

            return redirect()->route('dashboard.menus.edit', ['menu' => $menu, 'subdomain' => request()->route('subdomain')])
                ->with('success', 'Menu created successfully. Now add items to your menu.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded image if exists
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            Log::error('Failed to create menu', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to create menu: '.$e->getMessage());
        }
    }

    /**
     * Display the specified menu.
     */
    public function show(string $subdomain, Menu $menu)
    {
        $menu->load(['items.modifierGroups.modifiers', 'locations']);

        // Get platform sync status
        $platformSyncs = DB::table('menu_platform')
            ->where('menu_id', $menu->id)
            ->get()
            ->keyBy('platform');

        return view('dashboard.menus.show', compact('menu', 'platformSyncs'));
    }

    /**
     * Show the form for editing the menu.
     */
    public function edit(string $subdomain, Menu $menu)
    {
        $menu->load(['items.modifierGroups', 'locations']);
        $locations = Location::active()->orderBy('name')->get();
        $platforms = $menu->platforms();

        return view('dashboard.menus.edit', compact('menu', 'locations', 'platforms'));
    }

    /**
     * Update the specified menu.
     */
    public function update(Request $request, string $subdomain, Menu $menu)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'remove_image' => 'boolean',
            'is_active' => 'boolean',
            'locations' => 'nullable|array',
            'locations.*' => 'exists:locations,id',
            'platforms' => 'nullable|array',
            'platforms.*' => 'in:careem,talabat',
        ]);

        DB::beginTransaction();

        try {
            // Handle image removal
            if ($request->has('remove_image') && $menu->image_url) {
                Storage::disk('public')->delete($menu->image_url);
                $menu->image_url = null;
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image
                if ($menu->image_url) {
                    Storage::disk('public')->delete($menu->image_url);
                }
                $menu->image_url = $request->file('image')->store('menus', 'public');
            }

            // Update menu
            $menu->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $request->has('is_active'),
            ]);

            // Sync locations
            if (isset($validated['locations'])) {
                $locationData = [];
                foreach ($validated['locations'] as $locationId) {
                    $locationData[$locationId] = [
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $menu->locations()->sync($locationData);
            } else {
                $menu->locations()->detach();
            }

            // Sync platforms
            $currentPlatforms = $menu->platforms();
            $newPlatforms = $validated['platforms'] ?? [];

            // Remove platforms not in new list
            foreach ($currentPlatforms as $platform) {
                if (! in_array($platform, $newPlatforms)) {
                    $menu->removeFromPlatform($platform);
                }
            }

            // Add new platforms
            foreach ($newPlatforms as $platform) {
                if (! in_array($platform, $currentPlatforms)) {
                    $menu->assignToPlatform($platform);
                }
            }

            DB::commit();

            return redirect()->route('dashboard.menus.index', ['subdomain' => request()->route('subdomain')])
                ->with('success', 'Menu updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update menu', [
                'menu_id' => $menu->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to update menu: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified menu.
     */
    public function destroy(string $subdomain, Menu $menu)
    {
        DB::beginTransaction();

        try {
            // Delete image if exists
            if ($menu->image_url) {
                Storage::disk('public')->delete($menu->image_url);
            }

            $menu->delete();

            DB::commit();

            return redirect()->route('dashboard.menus.index', ['subdomain' => request()->route('subdomain')])
                ->with('success', 'Menu deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete menu', [
                'menu_id' => $menu->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete menu: '.$e->getMessage());
        }
    }

    /**
     * Toggle menu active status.
     */
    public function toggle(string $subdomain, Menu $menu)
    {
        $menu->is_active = ! $menu->is_active;
        $menu->save();

        return back()->with('success', 'Menu status updated successfully.');
    }

    /**
     * Publish the menu.
     */
    public function publish(string $subdomain, Menu $menu)
    {
        // Validate menu has items
        if ($menu->items()->count() === 0) {
            return back()->with('error', 'Cannot publish an empty menu. Please add items first.');
        }

        // Validate menu has platforms
        $platforms = $menu->platforms();
        if (empty($platforms)) {
            return back()->with('error', 'Cannot publish menu. Please assign at least one platform.');
        }

        // Validate menu has locations
        if ($menu->activeLocations()->count() === 0) {
            return back()->with('error', 'Cannot publish menu. Please assign at least one location.');
        }

        DB::beginTransaction();

        try {
            // Mark menu as published
            $menu->publish();

            // Get tenant ID (cast to int for type safety)
            $tenantId = (int) $menu->tenant_id;

            // Dispatch sync jobs for each platform
            foreach ($platforms as $platform) {
                if (config("platforms.{$platform}.enabled", true)) {
                    \App\Jobs\SyncMenuToPlatformJob::dispatch($menu, $platform, $tenantId);

                    Log::info("Menu sync job dispatched for {$platform}", [
                        'menu_id' => $menu->id,
                        'menu_name' => $menu->name,
                        'platform' => $platform,
                    ]);
                }
            }

            DB::commit();

            // Log activity
            \App\Services\UserActivityService::log('menu.published', null, [
                'menu_id' => $menu->id,
                'menu_name' => $menu->name,
                'platforms' => $platforms,
            ]);

            $platformList = implode(', ', array_map('ucfirst', $platforms));

            return back()->with('success', "Menu published successfully! Syncing to platforms: {$platformList}. This may take a few minutes.");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to publish menu', [
                'menu_id' => $menu->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to publish menu: '.$e->getMessage());
        }
    }

    /**
     * Unpublish the menu.
     */
    public function unpublish(string $subdomain, Menu $menu)
    {
        $menu->unpublish();

        return back()->with('success', 'Menu unpublished successfully.');
    }

    /**
     * Duplicate the menu.
     */
    public function duplicate(string $subdomain, Menu $menu)
    {
        DB::beginTransaction();

        try {
            // Create new menu
            $newMenu = $menu->replicate();
            $newMenu->name = $menu->name.' (Copy)';
            $newMenu->status = 'draft';
            $newMenu->published_at = null;
            $newMenu->save();

            // Duplicate items
            foreach ($menu->items as $item) {
                $newItem = $item->replicate();
                $newItem->menu_id = $newMenu->id;
                $newItem->save();

                // Duplicate modifier group assignments
                foreach ($item->modifierGroups as $modifierGroup) {
                    $newItem->modifierGroups()->attach($modifierGroup->id, [
                        'sort_order' => $modifierGroup->pivot->sort_order,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Duplicate location assignments
            foreach ($menu->locations as $location) {
                $newMenu->locations()->attach($location->id, [
                    'is_active' => $location->pivot->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Duplicate platform assignments
            foreach ($menu->platforms() as $platform) {
                $newMenu->assignToPlatform($platform);
            }

            DB::commit();

            return redirect()->route('dashboard.menus.edit', ['menu' => $newMenu, 'subdomain' => request()->route('subdomain')])
                ->with('success', 'Menu duplicated successfully. You can now edit the copy.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to duplicate menu', [
                'menu_id' => $menu->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to duplicate menu: '.$e->getMessage());
        }
    }
}
