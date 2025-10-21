<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LocationController extends Controller
{
    /**
     * Display a listing of locations.
     */
    public function index(Request $request, string $subdomain)
    {
        // The HasTenant trait automatically scopes to current tenant
        $locations = Location::paginate(12);

        return view('dashboard.locations.index', compact('locations'));
    }

    /**
     * Show the form for creating a new location.
     */
    public function create(string $subdomain)
    {
        // Days of week for opening hours
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return view('dashboard.locations.create', compact('days'));
    }

    /**
     * Store a newly created location in storage.
     */
    public function store(Request $request, string $subdomain)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'platforms' => 'array|min:1',
            'platforms.*' => 'in:careem,talabat',
            'opening_hours' => 'nullable|array',
            'opening_hours.monday' => 'nullable|array',
            'opening_hours.monday.open' => 'nullable|date_format:H:i',
            'opening_hours.monday.close' => 'nullable|date_format:H:i',
            'opening_hours.tuesday' => 'nullable|array',
            'opening_hours.tuesday.open' => 'nullable|date_format:H:i',
            'opening_hours.tuesday.close' => 'nullable|date_format:H:i',
            'opening_hours.wednesday' => 'nullable|array',
            'opening_hours.wednesday.open' => 'nullable|date_format:H:i',
            'opening_hours.wednesday.close' => 'nullable|date_format:H:i',
            'opening_hours.thursday' => 'nullable|array',
            'opening_hours.thursday.open' => 'nullable|date_format:H:i',
            'opening_hours.thursday.close' => 'nullable|date_format:H:i',
            'opening_hours.friday' => 'nullable|array',
            'opening_hours.friday.open' => 'nullable|date_format:H:i',
            'opening_hours.friday.close' => 'nullable|date_format:H:i',
            'opening_hours.saturday' => 'nullable|array',
            'opening_hours.saturday.open' => 'nullable|date_format:H:i',
            'opening_hours.saturday.close' => 'nullable|date_format:H:i',
            'opening_hours.sunday' => 'nullable|array',
            'opening_hours.sunday.open' => 'nullable|date_format:H:i',
            'opening_hours.sunday.close' => 'nullable|date_format:H:i',
            'loyverse_store_id' => 'nullable|string|max:255',
        ]);

        // Clean up opening hours - remove empty entries
        if (isset($validated['opening_hours'])) {
            $hours = $validated['opening_hours'];
            foreach ($hours as $day => $times) {
                if (empty($times['open']) || empty($times['close'])) {
                    unset($hours[$day]);
                }
            }
            $validated['opening_hours'] = $hours ?: null;
        }

        $validated['is_active'] = true;
        $validated['is_busy'] = false;

        Location::create($validated);

        return redirect()
            ->route('dashboard.locations.index', ['subdomain' => request()->route('subdomain')])
            ->with('success', 'Location created successfully.');
    }

    /**
     * Show the form for editing the specified location.
     */
    public function edit(string $subdomain, Location $location)
    {
        $this->authorizeLocation($location);

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return view('dashboard.locations.edit', compact('location', 'days'));
    }

    /**
     * Update the specified location in storage.
     */
    public function update(Request $request, string $subdomain, Location $location)
    {
        $this->authorizeLocation($location);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'platforms' => 'array|min:1',
            'platforms.*' => 'in:careem,talabat',
            'opening_hours' => 'nullable|array',
            'opening_hours.monday' => 'nullable|array',
            'opening_hours.monday.open' => 'nullable|date_format:H:i',
            'opening_hours.monday.close' => 'nullable|date_format:H:i',
            'opening_hours.tuesday' => 'nullable|array',
            'opening_hours.tuesday.open' => 'nullable|date_format:H:i',
            'opening_hours.tuesday.close' => 'nullable|date_format:H:i',
            'opening_hours.wednesday' => 'nullable|array',
            'opening_hours.wednesday.open' => 'nullable|date_format:H:i',
            'opening_hours.wednesday.close' => 'nullable|date_format:H:i',
            'opening_hours.thursday' => 'nullable|array',
            'opening_hours.thursday.open' => 'nullable|date_format:H:i',
            'opening_hours.thursday.close' => 'nullable|date_format:H:i',
            'opening_hours.friday' => 'nullable|array',
            'opening_hours.friday.open' => 'nullable|date_format:H:i',
            'opening_hours.friday.close' => 'nullable|date_format:H:i',
            'opening_hours.saturday' => 'nullable|array',
            'opening_hours.saturday.open' => 'nullable|date_format:H:i',
            'opening_hours.saturday.close' => 'nullable|date_format:H:i',
            'opening_hours.sunday' => 'nullable|array',
            'opening_hours.sunday.open' => 'nullable|date_format:H:i',
            'opening_hours.sunday.close' => 'nullable|date_format:H:i',
            'loyverse_store_id' => 'nullable|string|max:255',
        ]);

        // Clean up opening hours - remove empty entries
        if (isset($validated['opening_hours'])) {
            $hours = $validated['opening_hours'];
            foreach ($hours as $day => $times) {
                if (empty($times['open']) || empty($times['close'])) {
                    unset($hours[$day]);
                }
            }
            $validated['opening_hours'] = $hours ?: null;
        }

        $location->update($validated);

        return redirect()
            ->route('dashboard.locations.index', ['subdomain' => request()->route('subdomain')])
            ->with('success', 'Location updated successfully.');
    }

    /**
     * Delete the specified location.
     */
    public function destroy(string $subdomain, Location $location)
    {
        $this->authorizeLocation($location);

        $location->delete();

        return redirect()
            ->route('dashboard.locations.index', ['subdomain' => request()->route('subdomain')])
            ->with('success', 'Location deleted successfully.');
    }

    /**
     * Toggle location busy mode.
     */
    public function toggleBusy(string $subdomain, Location $location)
    {
        $this->authorizeLocation($location);

        $location->toggleBusyMode();

        return response()->json([
            'success' => true,
            'is_busy' => $location->is_busy,
            'message' => $location->is_busy ? 'Location marked as busy' : 'Location marked as available',
        ]);
    }

    /**
     * Toggle location active status.
     */
    public function toggle(string $subdomain, Location $location)
    {
        $this->authorizeLocation($location);

        $location->is_active = !$location->is_active;
        $location->save();

        return response()->json([
            'success' => true,
            'is_active' => $location->is_active,
            'message' => $location->is_active ? 'Location activated' : 'Location deactivated',
        ]);
    }

    /**
     * Authorize the location belongs to current tenant.
     * Note: Route model binding with HasTenant trait already ensures tenant scoping.
     */
    private function authorizeLocation(Location $location)
    {
        // The HasTenant trait with TenantScope already ensures the location
        // belongs to the current tenant through route model binding
        // No additional authorization needed here
    }
}
