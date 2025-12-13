<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Careem Brand
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Info Box -->
                    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="w-5 h-5 text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="text-sm text-blue-700">
                                <p class="font-semibold mb-1">Brand ID Guidelines</p>
                                <p>Use a unique, short identifier (e.g., "KFC", "SUBWAY"). This ID must be unique across all Careem partners.</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('dashboard.careem-brands.store', ['subdomain' => request()->route('subdomain')]) }}">
                        @csrf

                        <!-- Brand ID -->
                        <div class="mb-4">
                            <label for="careem_brand_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Brand ID *
                            </label>
                            <input type="text"
                                   name="careem_brand_id"
                                   id="careem_brand_id"
                                   value="{{ old('careem_brand_id') }}"
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('careem_brand_id') border-red-500 @enderror"
                                   placeholder="e.g., KFC">
                            @error('careem_brand_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">A unique identifier for your brand (alphanumeric, no spaces)</p>
                        </div>

                        <!-- Brand Name -->
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Brand Name *
                            </label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name') }}"
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('name') border-red-500 @enderror"
                                   placeholder="e.g., Kentucky Fried Chicken">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Sync to Careem Checkbox -->
                        <div class="mb-6">
                            <label class="flex items-start">
                                <input type="checkbox"
                                       name="sync_to_careem"
                                       id="sync_to_careem"
                                       value="1"
                                       {{ old('sync_to_careem') ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 mt-0.5">
                                <span class="ml-2">
                                    <span class="text-sm font-medium text-gray-700">Sync to Careem immediately</span>
                                    <span class="block text-xs text-gray-500 mt-1">If checked, the brand will be created in Careem API right away. Otherwise, you can sync it later manually.</span>
                                </span>
                            </label>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('dashboard.careem-brands.index', ['subdomain' => request()->route('subdomain')]) }}"
                               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Create Brand
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
