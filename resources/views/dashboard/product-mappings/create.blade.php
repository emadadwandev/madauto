<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Product Mapping') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('product-mappings.store', ['subdomain' => request()->route('subdomain')]) }}">
                        @csrf

                        <div class="mb-4">
                            <label for="platform" class="block text-gray-700 text-sm font-bold mb-2">
                                Platform *
                            </label>
                            <select name="platform" id="platform" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('platform') border-red-500 @enderror">
                                <option value="">Select a platform...</option>
                                <option value="careem" {{ old('platform') === 'careem' ? 'selected' : '' }}>Careem</option>
                                <option value="talabat" {{ old('platform') === 'talabat' ? 'selected' : '' }}>Talabat</option>
                            </select>
                            @error('platform')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-600 text-xs italic mt-1">Select the delivery platform this product belongs to</p>
                        </div>

                        <div class="mb-4">
                            <label for="platform_product_id" class="block text-gray-700 text-sm font-bold mb-2">
                                Platform Product ID *
                            </label>
                            <input type="text" name="platform_product_id" id="platform_product_id" value="{{ old('platform_product_id') }}" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('platform_product_id') border-red-500 @enderror">
                            @error('platform_product_id')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-600 text-xs italic mt-1">The unique product ID from the delivery platform</p>
                        </div>

                        <div class="mb-4">
                            <label for="platform_name" class="block text-gray-700 text-sm font-bold mb-2">
                                Platform Product Name *
                            </label>
                            <input type="text" name="platform_name" id="platform_name" value="{{ old('platform_name') }}" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('platform_name') border-red-500 @enderror">
                            @error('platform_name')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-600 text-xs italic mt-1">The product name as shown on the delivery platform</p>
                        </div>

                        <div class="mb-4">
                            <label for="platform_sku" class="block text-gray-700 text-sm font-bold mb-2">
                                Platform SKU (Optional)
                            </label>
                            <input type="text" name="platform_sku" id="platform_sku" value="{{ old('platform_sku') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('platform_sku') border-red-500 @enderror">
                            @error('platform_sku')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-600 text-xs italic mt-1">The SKU from the delivery platform (if available)</p>
                        </div>

                        <div class="mb-4" x-data="{ search: '', selectedItem: null }">
                            <label for="loyverse_item_search" class="block text-gray-700 text-sm font-bold mb-2">
                                Loyverse Item * <span class="text-gray-500 font-normal text-xs">({{ count($loyverseItems) }} items available)</span>
                            </label>

                            <!-- Search Box -->
                            <input
                                type="text"
                                x-model="search"
                                placeholder="Search by name, SKU, or category..."
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline mb-2"
                            >

                            <!-- Select Dropdown -->
                            <select
                                name="loyverse_item_id"
                                id="loyverse_item_id"
                                required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('loyverse_item_id') border-red-500 @enderror"
                                x-on:change="selectedItem = $event.target.value;
                                             const option = $event.target.options[$event.target.selectedIndex];
                                             const variantId = option.dataset.variantId;
                                             if (variantId) document.getElementById('loyverse_variant_id').value = variantId;"
                            >
                                <option value="">Select a Loyverse item...</option>
                                @foreach ($loyverseItems as $item)
                                    <option
                                        value="{{ $item['id'] }}"
                                        data-variant-id="{{ $item['variant_id'] ?? '' }}"
                                        {{ old('loyverse_item_id') === $item['id'] ? 'selected' : '' }}
                                        x-show="search === '' || '{{ strtolower($item['name'] ?? '') }} {{ strtolower($item['sku'] ?? '') }} {{ strtolower($item['category'] ?? '') }}'.includes(search.toLowerCase())"
                                    >
                                        {{ $item['name'] }}
                                        @if($item['sku']) | SKU: {{ $item['sku'] }} @endif
                                        @if($item['price']) | {{ number_format($item['price'], 2) }} @endif
                                        @if($item['category']) | {{ $item['category'] }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('loyverse_item_id')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-600 text-xs italic mt-1">
                                @if(count($loyverseItems) === 0)
                                    <span class="text-orange-600">⚠ No Loyverse items found. Please check your API credentials or create items in Loyverse first.</span>
                                @else
                                    Use the search box above to filter items by name, SKU, or category
                                @endif
                            </p>
                        </div>

                        <div class="mb-6">
                            <label for="loyverse_variant_id" class="block text-gray-700 text-sm font-bold mb-2">
                                Loyverse Variant ID (Optional)
                            </label>
                            <input type="text" name="loyverse_variant_id" id="loyverse_variant_id" value="{{ old('loyverse_variant_id') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('loyverse_variant_id') border-red-500 @enderror">
                            <p class="text-gray-600 text-xs italic mt-1">Auto-filled when item with variants is selected. Modify only if needed.</p>
                            @error('loyverse_variant_id')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Create Mapping
                            </button>
                            <a href="{{ route('product-mappings.index', ['subdomain' => request()->route('subdomain')]) }}" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
