<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Brand: {{ $brand->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('dashboard.careem-brands.update', ['subdomain' => request()->route('subdomain'), 'careemBrand' => $brand->id]) }}">
                        @csrf
                        @method('PUT')

                        <!-- Brand ID (Read-only) -->
                        <div class="mb-4">
                            <label for="careem_brand_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Brand ID
                            </label>
                            <input type="text"
                                   value="{{ $brand->careem_brand_id }}"
                                   disabled
                                   class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Brand ID cannot be changed after creation</p>
                        </div>

                        <!-- Brand Name -->
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Brand Name *
                            </label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name', $brand->name) }}"
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('name') border-red-500 @enderror">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Sync to Careem Checkbox -->
                        <div class="mb-6">
                            <label class="flex items-start">
                                <input type="checkbox"
                                       name="sync_to_careem"
                                       value="1"
                                       {{ old('sync_to_careem') ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 mt-0.5">
                                <span class="ml-2">
                                    <span class="text-sm font-medium text-gray-700">Sync changes to Careem</span>
                                    <span class="block text-xs text-gray-500 mt-1">If checked, changes will be synced to Careem API immediately.</span>
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
                                Update Brand
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
