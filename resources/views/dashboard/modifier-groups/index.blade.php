<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Modifier Groups') }}
            </h2>
            <a href="{{ route('dashboard.modifier-groups.create', ['subdomain' => request()->route('subdomain')]) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Modifier Group
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if (session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('dashboard.modifier-groups.index', ['subdomain' => request()->route('subdomain')]) }}" class="flex gap-4">
                        <div class="flex-1">
                            <input type="text"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Search modifier groups..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <select name="status"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Filter
                        </button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('dashboard.modifier-groups.index', ['subdomain' => request()->route('subdomain')]) }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                                Clear
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Modifier Groups Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($modifierGroups as $group)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                        <div class="p-6">
                            <!-- Header -->
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $group->name }}</h3>
                                    @if($group->description)
                                        <p class="text-sm text-gray-600 mt-1">{{ Str::limit($group->description, 60) }}</p>
                                    @endif
                                </div>
                                <form action="{{ route('dashboard.modifier-groups.toggle', ['modifierGroup' => $group, 'subdomain' => request()->route('subdomain')]) }}" method="POST" class="ml-2">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $group->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $group->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </div>

                            <!-- Group Settings -->
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="capitalize">{{ $group->selection_type }}</span> selection
                                </div>

                                @if($group->is_required)
                                    <div class="flex items-center text-sm text-red-600">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        Required
                                    </div>
                                @endif

                                @if($group->min_selections > 0 || $group->max_selections)
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        Min: {{ $group->min_selections }} / Max: {{ $group->max_selections ?? '∞' }}
                                    </div>
                                @endif
                            </div>

                            <!-- Modifiers List -->
                            <div class="border-t border-gray-200 pt-4 mb-4">
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Modifiers ({{ $group->modifiers->count() }})</div>
                                @if($group->modifiers->count() > 0)
                                    <div class="space-y-1">
                                        @foreach($group->modifiers->take(3) as $modifier)
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="text-gray-700">{{ $modifier->name }}</span>
                                                <span class="text-gray-500">{{ $modifier->formatted_price }}</span>
                                            </div>
                                        @endforeach
                                        @if($group->modifiers->count() > 3)
                                            <div class="text-xs text-gray-500 italic">
                                                +{{ $group->modifiers->count() - 3 }} more
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 italic">No modifiers assigned</p>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('dashboard.modifier-groups.edit', ['modifierGroup' => $group, 'subdomain' => request()->route('subdomain')]) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded text-xs text-white hover:bg-indigo-700">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </a>
                                <form action="{{ route('dashboard.modifier-groups.destroy', ['modifierGroup' => $group, 'subdomain' => request()->route('subdomain')]) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            onclick="return confirm('Are you sure you want to delete this modifier group?')"
                                            class="inline-flex items-center px-3 py-1.5 bg-red-600 border border-transparent rounded text-xs text-white hover:bg-red-700">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="bg-white shadow-sm sm:rounded-lg p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No modifier groups</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating a new modifier group.</p>
                            <div class="mt-6">
                                <a href="{{ route('dashboard.modifier-groups.create', ['subdomain' => request()->route('subdomain')]) }}"
                                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Create Modifier Group
                                </a>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($modifierGroups->hasPages())
                <div class="mt-6">
                    {{ $modifierGroups->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
