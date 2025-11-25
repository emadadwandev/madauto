<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Product Mapping') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('product-mappings.update', ['id' => $productMapping->id, 'subdomain' => request()->route('subdomain')]) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="platform" class="block text-gray-700 text-sm font-bold mb-2">
                                Platform *
                            </label>
                            <select name="platform" id="platform" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('platform') border-red-500 @enderror">
                                <option value="">Select a platform...</option>
                                <option value="careem" {{ old('platform', $productMapping->platform) === 'careem' ? 'selected' : '' }}>Careem</option>
                                <option value="talabat" {{ old('platform', $productMapping->platform) === 'talabat' ? 'selected' : '' }}>Talabat</option>
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
                            <input type="text" name="platform_product_id" id="platform_product_id" value="{{ old('platform_product_id', $productMapping->platform_product_id) }}" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('platform_product_id') border-red-500 @enderror">
                            @error('platform_product_id')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-600 text-xs italic mt-1">The unique product ID from the delivery platform</p>
                        </div>

                        <div class="mb-4">
                            <label for="platform_name" class="block text-gray-700 text-sm font-bold mb-2">
                                Platform Product Name *
                            </label>
                            <input type="text" name="platform_name" id="platform_name" value="{{ old('platform_name', $productMapping->platform_name) }}" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('platform_name') border-red-500 @enderror">
                            @error('platform_name')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-600 text-xs italic mt-1">The product name as shown on the delivery platform</p>
                        </div>

                        <div class="mb-4">
                            <label for="platform_sku" class="block text-gray-700 text-sm font-bold mb-2">
                                Platform SKU (Optional)
                            </label>
                            <input type="text" name="platform_sku" id="platform_sku" value="{{ old('platform_sku', $productMapping->platform_sku) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('platform_sku') border-red-500 @enderror">
                            @error('platform_sku')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-gray-600 text-xs italic mt-1">The SKU from the delivery platform (if available)</p>
                        </div>

                        <div class="mb-4">
                            <label for="loyverse_item_id" class="block text-gray-700 text-sm font-bold mb-2">
                                Loyverse Item *
                            </label>
                            <select name="loyverse_item_id" id="loyverse_item_id" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('loyverse_item_id') border-red-500 @enderror">
                                <option value="">Select a Loyverse item...</option>
                                @foreach ($loyverseItems as $item)
                                    <option value="{{ $item['id'] }}" {{ old('loyverse_item_id', $productMapping->loyverse_item_id) === $item['id'] ? 'selected' : '' }}>
                                        {{ $item['name'] }} (ID: {{ $item['id'] }})
                                    </option>
                                @endforeach
                            </select>
                            @error('loyverse_item_id')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="loyverse_variant_id" class="block text-gray-700 text-sm font-bold mb-2">
                                Loyverse Variant ID (Optional)
                            </label>
                            <input type="text" name="loyverse_variant_id" id="loyverse_variant_id" value="{{ old('loyverse_variant_id', $productMapping->loyverse_variant_id) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('loyverse_variant_id') border-red-500 @enderror">
                            <p class="text-gray-600 text-xs italic mt-1">Only required if the item has variants</p>
                            @error('loyverse_variant_id')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $productMapping->is_active) ? 'checked' : '' }} class="form-checkbox h-5 w-5 text-blue-600">
                                <span class="ml-2 text-gray-700">Active</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-between">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Update Mapping
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
