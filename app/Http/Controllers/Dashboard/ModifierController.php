<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Modifier;
use App\Services\LoyverseApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ModifierController extends Controller
{
    protected $loyverseApiService;

    public function __construct(LoyverseApiService $loyverseApiService)
    {
        $this->loyverseApiService = $loyverseApiService;
    }

    /**
     * Display a listing of modifiers.
     */
    public function index(Request $request)
    {
        $query = Modifier::query();

        // Filter by active status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $modifiers = $query->orderBy('name')->paginate(20);

        return view('dashboard.modifiers.index', compact('modifiers'));
    }

    /**
     * Show the form for creating a new modifier.
     */
    public function create()
    {
        return view('dashboard.modifiers.create');
    }

    /**
     * Store a newly created modifier.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_adjustment' => 'required|numeric',
            'sku' => 'nullable|string|max:255',
            'loyverse_modifier_id' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Modifier::create($validated);

        return redirect()->route('dashboard.modifiers.index')
            ->with('success', 'Modifier created successfully.');
    }

    /**
     * Show the form for editing the modifier.
     */
    public function edit(string $subdomain, Modifier $modifier)
    {
        return view('dashboard.modifiers.edit', compact('modifier'));
    }

    /**
     * Update the specified modifier.
     */
    public function update(Request $request, string $subdomain, Modifier $modifier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price_adjustment' => 'required|numeric',
            'sku' => 'nullable|string|max:255',
            'loyverse_modifier_id' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $modifier->update($validated);

        return redirect()->route('dashboard.modifiers.index')
            ->with('success', 'Modifier updated successfully.');
    }

    /**
     * Remove the specified modifier.
     */
    public function destroy(string $subdomain, Modifier $modifier)
    {
        $modifier->delete();

        return redirect()->route('dashboard.modifiers.index')
            ->with('success', 'Modifier deleted successfully.');
    }

    /**
     * Toggle modifier active status.
     */
    public function toggle(string $subdomain, Modifier $modifier)
    {
        $modifier->is_active = !$modifier->is_active;
        $modifier->save();

        return back()->with('success', 'Modifier status updated successfully.');
    }

    /**
     * Sync modifiers from Loyverse API.
     */
    public function syncFromLoyverse()
    {
        try {
            if (! $this->loyverseApiService->hasCredentials()) {
                return back()->with('error', 'Loyverse API credentials not configured. Please configure them in Settings.');
            }

            // Fetch all items with their modifiers
            $items = $this->loyverseApiService->getAllItems();

            $syncedCount = 0;
            $modifiersProcessed = [];

            foreach ($items as $item) {
                if (isset($item['modifiers']) && is_array($item['modifiers'])) {
                    foreach ($item['modifiers'] as $modifierData) {
                        $modifierId = $modifierData['modifier_id'] ?? $modifierData['id'] ?? null;

                        if ($modifierId && ! in_array($modifierId, $modifiersProcessed)) {
                            Modifier::syncFromLoyverse([
                                'id' => $modifierId,
                                'name' => $modifierData['modifier_name'] ?? $modifierData['name'] ?? 'Unnamed Modifier',
                                'price' => $modifierData['price'] ?? 0,
                            ]);

                            $modifiersProcessed[] = $modifierId;
                            $syncedCount++;
                        }
                    }
                }
            }

            return back()->with('success', "Successfully synced {$syncedCount} modifiers from Loyverse.");
        } catch (\Exception $e) {
            Log::error('Failed to sync modifiers from Loyverse', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to sync modifiers: '.$e->getMessage());
        }
    }
}
