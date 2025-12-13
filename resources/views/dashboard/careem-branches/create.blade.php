<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Careem Branch
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
                                <p class="font-semibold mb-1">Branch Creation Guidelines</p>
                                <p>Each branch must belong to a brand. Use a unique branch ID (e.g., "KFC_MARINA"). Optionally map to a local location to sync orders with Loyverse.</p>
                            </div>
                        </div>
                    </div>

                    @if($brands->count() === 0)
                        <!-- No Brands Warning -->
                        <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-yellow-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div class="text-sm text-yellow-700">
                                    <p class="font-semibold mb-1">No Brands Available</p>
                                    <p>You must create at least one brand before creating branches.</p>
                                    <a href="{{ route('dashboard.careem-brands.create', ['subdomain' => request()->route('subdomain')]) }}"
                                       class="underline mt-2 inline-block">Create a brand first</a>
                                </div>
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('dashboard.careem-branches.store', ['subdomain' => request()->route('subdomain')]) }}">
                            @csrf

                            <!-- Brand Selection -->
                            <div class="mb-4">
                                <label for="careem_brand_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Brand *
                                </label>
                                <select name="careem_brand_id"
                                        id="careem_brand_id"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('careem_brand_id') border-red-500 @enderror">
                                    <option value="">Select a brand</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ old('careem_brand_id') == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }} ({{ $brand->careem_brand_id }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('careem_brand_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Select the brand this branch belongs to</p>
                            </div>

                            <!-- Branch ID -->
                            <div class="mb-4">
                                <label for="careem_branch_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Branch ID *
                                </label>
                                <input type="text"
                                       name="careem_branch_id"
                                       id="careem_branch_id"
                                       value="{{ old('careem_branch_id') }}"
                                       required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('careem_branch_id') border-red-500 @enderror"
                                       placeholder="e.g., KFC_MARINA">
                                @error('careem_branch_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">A unique identifier for this branch (alphanumeric, underscores allowed)</p>
                            </div>

                            <!-- Branch Name -->
                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Branch Name *
                                </label>
                                <input type="text"
                                       name="name"
                                       id="name"
                                       value="{{ old('name') }}"
                                       required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('name') border-red-500 @enderror"
                                       placeholder="e.g., KFC, Marina Mall">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Location Mapping (Optional) -->
                            <div class="mb-4">
                                <label for="location_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Map to Local Location (Optional)
                                </label>
                                <select name="location_id"
                                        id="location_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('location_id') border-red-500 @enderror">
                                    <option value="">No location mapping</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('location_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Link this branch to an existing location for order synchronization</p>
                            </div>

                            <!-- POS Integration -->
                            <div class="mb-4">
                                <label class="flex items-start">
                                    <input type="checkbox"
                                           name="pos_integration_enabled"
                                           id="pos_integration_enabled"
                                           value="1"
                                           {{ old('pos_integration_enabled', true) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 mt-0.5">
                                    <span class="ml-2">
                                        <span class="text-sm font-medium text-gray-700">Enable POS Integration</span>
                                        <span class="block text-xs text-gray-500 mt-1">Allow this branch to receive orders from Careem</span>
                                    </span>
                                </label>
                            </div>

                            <!-- Visibility Status -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Visibility on Careem SuperApp
                                </label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="radio"
                                               name="visibility_status"
                                               value="1"
                                               {{ old('visibility_status', '1') == '1' ? 'checked' : '' }}
                                               class="border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <span class="ml-2 text-sm text-gray-700">Active - Visible to customers</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio"
                                               name="visibility_status"
                                               value="2"
                                               {{ old('visibility_status') == '2' ? 'checked' : '' }}
                                               class="border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <span class="ml-2 text-sm text-gray-700">Inactive - Hidden from customers</span>
                                    </label>
                                </div>
                                @error('visibility_status')
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
                                        <span class="block text-xs text-gray-500 mt-1">If checked, the branch will be created in Careem API right away. Otherwise, you can sync it later manually.</span>
                                    </span>
                                </label>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('dashboard.careem-branches.index', ['subdomain' => request()->route('subdomain')]) }}"
                                   class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </a>
                                <button type="submit"
                                        class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                    Create Branch
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
