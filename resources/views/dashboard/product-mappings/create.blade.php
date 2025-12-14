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

                        <div class="mb-4" x-data="{
                            search: '',
                            selectedItem: null,
                            refreshing: false,
                            itemCount: {{ count($loyverseItems) }},
                            refreshItems() {
                                this.refreshing = true;
                                fetch('{{ route('product-mappings.refresh-loyverse-items', ['subdomain' => request()->route('subdomain')]) }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    }
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (data.success) {
                                        window.location.reload();
                                    } else {
                                        alert('Failed to refresh items: ' + (data.message || 'Unknown error'));
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('Failed to refresh items. Please try again.');
                                })
                                .finally(() => {
                                    this.refreshing = false;
                                });
                            }
                        }">
                            <div class="flex justify-between items-center mb-2">
                                <label for="loyverse_item_search" class="text-gray-700 text-sm font-bold">
                                    Loyverse Item * <span class="text-gray-500 font-normal text-xs" x-text="`(${itemCount} items available)`"></span>
                                </label>
                                <button
                                    type="button"
                                    @click="refreshItems()"
                                    :disabled="refreshing"
                                    class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-3 h-3 mr-1" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span x-text="refreshing ? 'Refreshing...' : 'Refresh Items'"></span>
                                </button>
                            </div>

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
                                        @if($item['price']) | {{ formatCurrency($item['price']) }} @endif
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
