<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Item') }}: {{ $menuItem->name }}
            </h2>
            <a href="{{ route('dashboard.menus.edit', ['menu' => $menu, 'subdomain' => request()->route('subdomain')]) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Menu
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Error Messages -->
            @if (session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Whoops! Something went wrong.</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('dashboard.menus.items.update', ['menu' => $menu, 'menuItem' => $menuItem, 'subdomain' => request()->route('subdomain')]) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-6">
                                <h3 class="text-lg font-semibold text-gray-900">Item Details</h3>

                                <!-- Item Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Item Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           name="name"
                                           id="name"
                                           value="{{ old('name', $menuItem->name) }}"
                                           required
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>

                                <!-- Description -->
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                        Description
                                    </label>
                                    <textarea name="description"
                                              id="description"
                                              rows="3"
                                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $menuItem->description) }}</textarea>
                                </div>

                                <!-- Current Image -->
                                @if($menuItem->image_url)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Image</label>
                                        <div class="flex items-center gap-4">
                                            <img src="{{ Storage::url($menuItem->image_url) }}" alt="{{ $menuItem->name }}" class="w-32 h-32 object-cover rounded-lg">
                                            <div class="flex items-center">
                                                <input type="checkbox"
                                                       name="remove_image"
                                                       id="remove_image"
                                                       value="1"
                                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <label for="remove_image" class="ml-2 text-sm text-gray-700">
                                                    Remove image
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Image Upload -->
                                <div>
                                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ $menuItem->image_url ? 'Replace Image' : 'Upload Image' }}
                                    </label>
                                    <input type="file"
                                           name="image"
                                           id="image"
                                           accept="image/jpeg,image/jpg,image/png,image/webp"
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                    <p class="mt-1 text-xs text-gray-500">JPEG, JPG, PNG, WEBP. Max 2MB</p>
                                </div>

                                <!-- Category -->
                                <div>
                                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                        Category
                                    </label>
                                    <input type="text"
                                           name="category"
                                           id="category"
                                           value="{{ old('category', $menuItem->category) }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                           placeholder="e.g., Burgers, Appetizers">
                                    <p class="mt-1 text-xs text-gray-500">Items will be grouped by category in the menu</p>
                                </div>

                                <!-- SKU -->
                                <div>
                                    <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">
                                        SKU
                                    </label>
                                    <input type="text"
                                           name="sku"
                                           id="sku"
                                           value="{{ old('sku', $menuItem->sku) }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-6">
                                <h3 class="text-lg font-semibold text-gray-900">Pricing & Settings</h3>

                                <!-- Price -->
                                <div>
                                    <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                                        Price ({{ currencySymbol() }}) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number"
                                           name="price"
                                           id="price"
                                           value="{{ old('price', $menuItem->price) }}"
                                           step="0.01"
                                           min="0"
                                           required
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>

                                <!-- Tax Rate -->
                                <div>
                                    <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-2">
                                        Tax Rate (%) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number"
                                           name="tax_rate"
                                           id="tax_rate"
                                           value="{{ old('tax_rate', $menuItem->tax_rate) }}"
                                           step="0.01"
                                           min="0"
                                           max="100"
                                           required
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>

                                <!-- Default Quantity -->
                                <div>
                                    <label for="default_quantity" class="block text-sm font-medium text-gray-700 mb-2">
                                        Default Quantity <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number"
                                           name="default_quantity"
                                           id="default_quantity"
                                           value="{{ old('default_quantity', $menuItem->default_quantity) }}"
                                           min="1"
                                           required
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>

                                <!-- Loyverse Mapping -->
                                <div>
                                    <label for="loyverse_item_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Loyverse Item (Optional)
                                    </label>
                                    <select name="loyverse_item_id"
                                            id="loyverse_item_id"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">-- No mapping --</option>
                                        @foreach($loyverseItems as $loyverseItem)
                                            <option value="{{ $loyverseItem['id'] }}"
                                                {{ old('loyverse_item_id', $menuItem->loyverse_item_id) == $loyverseItem['id'] ? 'selected' : '' }}>
                                                {{ $loyverseItem['item_name'] }}
                                                @if(isset($loyverseItem['variants']) && count($loyverseItem['variants']) > 0)
                                                    ({{ count($loyverseItem['variants']) }} variants)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Map to Loyverse POS item for syncing</p>
                                </div>

                                <!-- Availability & Active -->
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <input type="checkbox"
                                               name="is_available"
                                               id="is_available"
                                               value="1"
                                               {{ old('is_available', $menuItem->is_available) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <label for="is_available" class="ml-2 text-sm text-gray-700">
                                            Available (in stock)
                                        </label>
                                    </div>

                                    <div class="flex items-center">
                                        <input type="checkbox"
                                               name="is_active"
                                               id="is_active"
                                               value="1"
                                               {{ old('is_active', $menuItem->is_active) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <label for="is_active" class="ml-2 text-sm text-gray-700">
                                            Active (item can be used)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modifier Groups Section -->
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Modifier Groups</h3>

                            @if($modifierGroups->count() > 0)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <p class="text-sm text-gray-600 mb-3">Select which modifier groups apply to this item:</p>

                                    @php
                                        $selectedGroups = old('modifier_groups', $menuItem->modifierGroups->pluck('id')->toArray());
                                    @endphp

                                    <div class="space-y-3">
                                        @foreach($modifierGroups as $group)
                                            <div class="bg-white rounded-lg p-3 border border-gray-200">
                                                <div class="flex items-start">
                                                    <input type="checkbox"
                                                           name="modifier_groups[]"
                                                           id="group_{{ $group->id }}"
                                                           value="{{ $group->id }}"
                                                           {{ in_array($group->id, $selectedGroups) ? 'checked' : '' }}
                                                           class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <label for="group_{{ $group->id }}" class="ml-3 flex-1">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-sm font-medium text-gray-900">{{ $group->name }}</span>
                                                            @if($group->is_required)
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                                    Required
                                                                </span>
                                                            @endif
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                                {{ ucfirst($group->selection_type) }}
                                                            </span>
                                                        </div>
                                                        @if($group->description)
                                                            <p class="text-xs text-gray-500 mt-1">{{ $group->description }}</p>
                                                        @endif
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            {{ $group->activeModifiers->count() }} modifiers
                                                            @if($group->selection_type === 'multiple')
                                                                (Select {{ $group->min_selections }} - {{ $group->max_selections }})
                                                            @endif
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="bg-gray-50 rounded-lg p-4 text-center">
                                    <p class="text-sm text-gray-500">No modifier groups available.                                     <a href="{{ route('dashboard.modifier-groups.create', ['subdomain' => request()->route('subdomain')]) }}" class="text-indigo-600 hover:text-indigo-800">Create one first</a>.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-gray-200">
                            <a href="{{ route('dashboard.menus.edit', ['menu' => $menu, 'subdomain' => request()->route('subdomain')]) }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Update Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
