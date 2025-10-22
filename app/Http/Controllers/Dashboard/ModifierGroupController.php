<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use Illuminate\Http\Request;

class ModifierGroupController extends Controller
{
    /**
     * Display a listing of modifier groups.
     */
    public function index(Request $request)
    {
        $query = ModifierGroup::with('modifiers');

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
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $modifierGroups = $query->orderBy('sort_order')->orderBy('name')->paginate(20);

        return view('dashboard.modifier-groups.index', compact('modifierGroups'));
    }

    /**
     * Show the form for creating a new modifier group.
     */
    public function create()
    {
        $modifiers = Modifier::active()->orderBy('name')->get();

        return view('dashboard.modifier-groups.create', compact('modifiers'));
    }

    /**
     * Store a newly created modifier group.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'selection_type' => 'required|in:single,multiple',
            'min_selections' => 'required|integer|min:0',
            'max_selections' => 'nullable|integer|min:1',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'modifiers' => 'nullable|array',
            'modifiers.*' => 'exists:modifiers,id',
            'modifier_sort_orders' => 'nullable|array',
            'default_modifiers' => 'nullable|array',
        ]);

        // Set boolean fields
        $validated['is_required'] = $request->has('is_required');
        $validated['is_active'] = $request->has('is_active');

        // Create modifier group
        $modifierGroup = ModifierGroup::create($validated);

        // Attach modifiers with sort order and default flags
        if ($request->has('modifiers')) {
            $syncData = [];
            foreach ($request->modifiers as $index => $modifierId) {
                $syncData[$modifierId] = [
                    'sort_order' => $request->modifier_sort_orders[$modifierId] ?? $index,
                    'is_default' => in_array($modifierId, $request->default_modifiers ?? []),
                ];
            }
            $modifierGroup->modifiers()->sync($syncData);
        }

        return redirect()->route('dashboard.modifier-groups.index', ['subdomain' => request()->route('subdomain')])
            ->with('success', 'Modifier group created successfully.');
    }

    /**
     * Display the specified modifier group.
     */
    public function show(string $subdomain, ModifierGroup $modifierGroup)
    {
        $modifierGroup->load('modifiers', 'menuItems');

        return view('dashboard.modifier-groups.show', compact('modifierGroup'));
    }

    /**
     * Show the form for editing the modifier group.
     */
    public function edit(string $subdomain, ModifierGroup $modifierGroup)
    {
        $modifierGroup->load('modifiers');
        $modifiers = Modifier::active()->orderBy('name')->get();

        return view('dashboard.modifier-groups.edit', compact('modifierGroup', 'modifiers'));
    }

    /**
     * Update the specified modifier group.
     */
    public function update(Request $request, string $subdomain, ModifierGroup $modifierGroup)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'selection_type' => 'required|in:single,multiple',
            'min_selections' => 'required|integer|min:0',
            'max_selections' => 'nullable|integer|min:1',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
            'modifiers' => 'nullable|array',
            'modifiers.*' => 'exists:modifiers,id',
            'modifier_sort_orders' => 'nullable|array',
            'default_modifiers' => 'nullable|array',
        ]);

        // Set boolean fields
        $validated['is_required'] = $request->has('is_required');
        $validated['is_active'] = $request->has('is_active');

        // Update modifier group
        $modifierGroup->update($validated);

        // Sync modifiers with sort order and default flags
        if ($request->has('modifiers')) {
            $syncData = [];
            foreach ($request->modifiers as $index => $modifierId) {
                $syncData[$modifierId] = [
                    'sort_order' => $request->modifier_sort_orders[$modifierId] ?? $index,
                    'is_default' => in_array($modifierId, $request->default_modifiers ?? []),
                ];
            }
            $modifierGroup->modifiers()->sync($syncData);
        } else {
            $modifierGroup->modifiers()->detach();
        }

        return redirect()->route('dashboard.modifier-groups.index', ['subdomain' => request()->route('subdomain')])
            ->with('success', 'Modifier group updated successfully.');
    }

    /**
     * Remove the specified modifier group.
     */
    public function destroy(string $subdomain, ModifierGroup $modifierGroup)
    {
        $modifierGroup->delete();

        return redirect()->route('dashboard.modifier-groups.index', ['subdomain' => request()->route('subdomain')])
            ->with('success', 'Modifier group deleted successfully.');
    }

    /**
     * Toggle modifier group active status.
     */
    public function toggle(string $subdomain, ModifierGroup $modifierGroup)
    {
        $modifierGroup->is_active = !$modifierGroup->is_active;
        $modifierGroup->save();

        return back()->with('success', 'Modifier group status updated successfully.');
    }

    /**
     * Reorder modifier groups.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:modifier_groups,id',
        ]);

        foreach ($request->order as $index => $id) {
            ModifierGroup::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
