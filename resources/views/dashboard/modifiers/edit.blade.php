<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Modifier') }}
            </h2>
            <a href="{{ route('dashboard.modifiers.index', ['subdomain' => request()->route('subdomain')]) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('dashboard.modifiers.update', ['modifier' => $modifier, 'subdomain' => request()->route('subdomain')]) }}">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Modifier Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name', $modifier->name) }}"
                                   required
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-500 @enderror">
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
                                      rows="3"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-500 @enderror">{{ old('description', $modifier->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Price Adjustment -->
                        <div class="mb-4">
                            <label for="price_adjustment" class="block text-sm font-medium text-gray-700 mb-2">
                                Price Adjustment <span class="text-red-500">*</span>
                            </label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">{{ currencySymbol() }}</span>
                                </div>
                                <input type="number"
                                       name="price_adjustment"
                                       id="price_adjustment"
                                       value="{{ old('price_adjustment', $modifier->price_adjustment) }}"
                                       step="0.01"
                                       required
                                       class="w-full pl-12 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('price_adjustment') border-red-500 @enderror">
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Positive for extra charge, negative for discount, 0 for free</p>
                            @error('price_adjustment')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- SKU -->
                        <div class="mb-4">
                            <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">
                                SKU (Optional)
                            </label>
                            <input type="text"
                                   name="sku"
                                   id="sku"
                                   value="{{ old('sku', $modifier->sku) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('sku') border-red-500 @enderror">
                            @error('sku')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Loyverse Modifier ID -->
                        <div class="mb-4">
                            <label for="loyverse_modifier_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Loyverse Modifier ID (Optional)
                            </label>
                            <input type="text"
                                   name="loyverse_modifier_id"
                                   id="loyverse_modifier_id"
                                   value="{{ old('loyverse_modifier_id', $modifier->loyverse_modifier_id) }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('loyverse_modifier_id') border-red-500 @enderror">
                            <p class="mt-1 text-sm text-gray-500">This modifier is {{ $modifier->loyverse_modifier_id ? 'synced with Loyverse' : 'not yet synced with Loyverse' }}</p>
                            @error('loyverse_modifier_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Active Status -->
                        <div class="mb-6">
                            <div class="flex items-center">
                                <input type="checkbox"
                                       name="is_active"
                                       id="is_active"
                                       value="1"
                                       {{ old('is_active', $modifier->is_active) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="is_active" class="ml-2 text-sm text-gray-700">
                                    Active (modifiers can only be used when active)
                                </label>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('dashboard.modifiers.index', ['subdomain' => request()->route('subdomain')]) }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Update Modifier
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
