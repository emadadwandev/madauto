<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Location: {{ $location->name }}
            </h2>
            <a href="{{ route('dashboard.locations.index', ['subdomain' => request()->route('subdomain')]) }}"
               class="text-sm text-gray-500 hover:text-gray-700">
                ← Back to Locations
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('dashboard.locations.update', ['location' => $location, 'subdomain' => request()->route('subdomain')]) }}" class="p-6">
                    @csrf
                    @method('PUT')

                    <!-- Basic Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>

                        <!-- Name -->
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Location Name *</label>
                            <input type="text" name="name" id="name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                   value="{{ old('name', $location->name) }}" placeholder="e.g., Downtown Branch">
                            @error('name')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" id="email"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                   value="{{ old('email', $location->email) }}" placeholder="location@restaurant.com">
                            @error('email')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div class="mb-4">
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel" name="phone" id="phone"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                   value="{{ old('phone', $location->phone) }}" placeholder="+971 4 123 4567">
                            @error('phone')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Address</h3>

                        <!-- Address Line 1 -->
                        <div class="mb-4">
                            <label for="address_line1" class="block text-sm font-medium text-gray-700 mb-2">Address Line 1 *</label>
                            <input type="text" name="address_line1" id="address_line1" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                   value="{{ old('address_line1', $location->address_line1) }}" placeholder="123 Main Street">
                            @error('address_line1')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Address Line 2 -->
                        <div class="mb-4">
                            <label for="address_line2" class="block text-sm font-medium text-gray-700 mb-2">Address Line 2</label>
                            <input type="text" name="address_line2" id="address_line2"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                   value="{{ old('address_line2', $location->address_line2) }}" placeholder="Suite 100">
                            @error('address_line2')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- City -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                                <input type="text" name="city" id="city" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                       value="{{ old('city', $location->city) }}" placeholder="Dubai">
                                @error('city')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- State -->
                            <div>
                                <label for="state" class="block text-sm font-medium text-gray-700 mb-2">State/Province</label>
                                <input type="text" name="state" id="state"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                       value="{{ old('state', $location->state) }}" placeholder="Dubai">
                                @error('state')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Postal Code & Country -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                                <input type="text" name="postal_code" id="postal_code"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                       value="{{ old('postal_code', $location->postal_code) }}" placeholder="12345">
                                @error('postal_code')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="country" class="block text-sm font-medium text-gray-700 mb-2">Country *</label>
                                <input type="text" name="country" id="country" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                       value="{{ old('country', $location->country) }}" placeholder="United Arab Emirates">
                                @error('country')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Platforms -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Connected Platforms *</h3>
                        <div class="space-y-3">
                            @foreach(['careem', 'talabat'] as $platform)
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="checkbox" name="platforms[]" value="{{ $platform }}"
                                           @checked(in_array($platform, old('platforms', $location->platforms ?? [])))
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-3 text-sm font-medium text-gray-700">{{ ucfirst($platform) }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('platforms')
                            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Opening Hours -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Opening Hours (Optional)</h3>
                        <p class="text-sm text-gray-600 mb-4">Set opening and closing times for each day. Leave empty to indicate closed.</p>

                        <div class="space-y-3">
                            @foreach($days as $day)
                                @php
                                    $dayHours = $location->opening_hours[$day] ?? [];
                                @endphp
                                <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                                    <label class="w-24 text-sm font-medium text-gray-700 capitalize">{{ $day }}</label>
                                    <input type="time" name="opening_hours[{{ $day }}][open]"
                                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                           value="{{ old("opening_hours.{$day}.open", $dayHours['open'] ?? '') }}">
                                    <span class="text-gray-500">to</span>
                                    <input type="time" name="opening_hours[{{ $day }}][close]"
                                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                           value="{{ old("opening_hours.{$day}.close", $dayHours['close'] ?? '') }}">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Loyverse Store ID -->
                    <div class="mb-8">
                        <label for="loyverse_store_id" class="block text-sm font-medium text-gray-700 mb-2">Loyverse Store ID</label>
                        <input type="text" name="loyverse_store_id" id="loyverse_store_id"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                               value="{{ old('loyverse_store_id', $location->loyverse_store_id) }}" placeholder="Store ID from Loyverse POS">
                        <p class="text-xs text-gray-500 mt-2">Optional: Link this location to a specific Loyverse store</p>
                        @error('loyverse_store_id')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 pt-6 border-t border-gray-200">
                        <a href="{{ route('dashboard.locations.index', ['subdomain' => request()->route('subdomain')]) }}"
                           class="px-6 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit"
                                class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                            Update Location
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
