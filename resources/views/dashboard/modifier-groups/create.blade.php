<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create Modifier Group') }}
            </h2>
            <a href="{{ route('dashboard.modifier-groups.index', ['subdomain' => request()->route('subdomain')]) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('dashboard.modifier-groups.store', ['subdomain' => request()->route('subdomain')]) }}" x-data="modifierGroupForm()">
                        @csrf

                        <!-- Name -->
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Group Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name') }}"
                                   required
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                                   placeholder="e.g., Size, Toppings, Add-ons">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Description
                            </label>
                            <textarea name="description"
                                      id="description"
                                      rows="2"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-500 @enderror"
                                      placeholder="Optional description for this modifier group">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <!-- Selection Type -->
                            <div>
                                <label for="selection_type" class="block text-sm font-medium text-gray-700 mb-2">
                                    Selection Type <span class="text-red-500">*</span>
                                </label>
                                <select name="selection_type"
                                        id="selection_type"
                                        required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('selection_type') border-red-500 @enderror">
                                    <option value="single" {{ old('selection_type') === 'single' ? 'selected' : '' }}>Single (Radio buttons)</option>
                                    <option value="multiple" {{ old('selection_type', 'multiple') === 'multiple' ? 'selected' : '' }}>Multiple (Checkboxes)</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Single = customer can select one option, Multiple = customer can select many</p>
                                @error('selection_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Sort Order -->
                            <div>
                                <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">
                                    Display Order
                                </label>
                                <input type="number"
                                       name="sort_order"
                                       id="sort_order"
                                       value="{{ old('sort_order', 0) }}"
                                       min="0"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-xs text-gray-500">Lower numbers appear first</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <!-- Min Selections -->
                            <div>
                                <label for="min_selections" class="block text-sm font-medium text-gray-700 mb-2">
                                    Minimum Selections <span class="text-red-500">*</span>
                                </label>
                                <input type="number"
                                       name="min_selections"
                                       id="min_selections"
                                       value="{{ old('min_selections', 0) }}"
                                       min="0"
                                       required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('min_selections') border-red-500 @enderror">
                                @error('min_selections')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Max Selections -->
                            <div>
                                <label for="max_selections" class="block text-sm font-medium text-gray-700 mb-2">
                                    Maximum Selections
                                </label>
                                <input type="number"
                                       name="max_selections"
                                       id="max_selections"
                                       value="{{ old('max_selections') }}"
                                       min="1"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="Leave blank for unlimited">
                                <p class="mt-1 text-xs text-gray-500">Leave blank for unlimited selections</p>
                            </div>
                        </div>

                        <!-- Checkboxes -->
                        <div class="mb-6 space-y-3">
                            <div class="flex items-center">
                                <input type="checkbox"
                                       name="is_required"
                                       id="is_required"
                                       value="1"
                                       {{ old('is_required') ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="is_required" class="ml-2 text-sm text-gray-700">
                                    Required (customer must select at least min_selections)
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox"
                                       name="is_active"
                                       id="is_active"
                                       value="1"
                                       {{ old('is_active', true) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="is_active" class="ml-2 text-sm text-gray-700">
                                    Active (group will be available for use)
                                </label>
                            </div>
                        </div>

                        <!-- Modifiers Selection -->
                        <div class="mb-6 border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Modifiers</h3>

                            @if($modifiers->count() > 0)
                                <div class="space-y-2 max-h-96 overflow-y-auto border border-gray-200 rounded-lg p-4">
                                    @foreach($modifiers as $modifier)
                                        <label class="flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded cursor-pointer">
                                            <input type="checkbox"
                                                   name="modifiers[]"
                                                   value="{{ $modifier->id }}"
                                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <span class="ml-3 flex-1 text-sm text-gray-700">{{ $modifier->name }}</span>
                                            <span class="text-sm text-gray-500">{{ $modifier->formatted_price }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="mt-2 text-sm text-gray-500">Select which modifiers belong to this group</p>
                            @else
                                <div class="text-center py-8 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-500">No modifiers available. Create modifiers first.</p>
                                    <a href="{{ route('dashboard.modifiers.create', ['subdomain' => request()->route('subdomain')]) }}" class="mt-2 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-700">
                                        Create a modifier →
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('dashboard.modifier-groups.index', ['subdomain' => request()->route('subdomain')]) }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Create Modifier Group
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function modifierGroupForm() {
            return {
                // Add any Alpine.js functionality here if needed
            }
        }
    </script>
    @endpush
</x-app-layout>
