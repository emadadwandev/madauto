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

                        <!-- Platform IDs -->
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="careem_store_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Careem Store ID
                                    <span class="text-gray-500 font-normal">(for Store API sync)</span>
                                </label>
                                <input type="text" name="careem_store_id" id="careem_store_id"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                       value="{{ old('careem_store_id', $location->careem_store_id) }}"
                                       placeholder="e.g., STORE-12345">
                                <p class="text-xs text-gray-500 mt-1">Required for syncing location status and hours to Careem</p>
                                @error('careem_store_id')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="talabat_vendor_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Talabat Vendor ID
                                    <span class="text-gray-500 font-normal">(for POS API sync)</span>
                                </label>
                                <input type="text" name="talabat_vendor_id" id="talabat_vendor_id"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                       value="{{ old('talabat_vendor_id', $location->talabat_vendor_id) }}"
                                       placeholder="e.g., VENDOR-67890">
                                <p class="text-xs text-gray-500 mt-1">Required for syncing location status to Talabat</p>
                                @error('talabat_vendor_id')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
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

            <!-- Platform Sync Section -->
            @if(count($location->platforms ?? []) > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6" x-data="locationPlatformSync()">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Platform Sync Management</h3>
                    <p class="text-sm text-gray-600 mb-6">Sync location status and operating hours to delivery platforms.</p>

                    <!-- Platform Sync Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        @if(in_array('careem', $location->platforms ?? []))
                        <!-- Careem Card -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">Careem</h4>
                                        @if($location->careem_store_id)
                                            <p class="text-xs text-gray-500">Store ID: {{ $location->careem_store_id }}</p>
                                        @else
                                            <p class="text-xs text-red-600">Store ID not configured</p>
                                        @endif
                                    </div>
                                </div>
                                @if(isset($location->platform_sync_status['careem']['status']))
                                    @if($location->platform_sync_status['careem']['status'] === 'success')
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Synced</span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Error</span>
                                    @endif
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Not Synced</span>
                                @endif
                            </div>

                            @if(isset($location->platform_sync_status['careem']['last_sync']))
                                <p class="text-xs text-gray-500 mb-3">
                                    Last synced: {{ \Carbon\Carbon::parse($location->platform_sync_status['careem']['last_sync'])->diffForHumans() }}
                                </p>
                            @endif

                            <div class="flex gap-2">
                                <button @click="syncStatus('careem')"
                                        :disabled="syncing"
                                        class="flex-1 px-3 py-2 text-sm bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">
                                    <span x-show="!syncing">Sync Status</span>
                                    <span x-show="syncing">Syncing...</span>
                                </button>
                                <button @click="syncHours('careem')"
                                        :disabled="syncing"
                                        class="flex-1 px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
                                    <span x-show="!syncing">Sync Hours</span>
                                    <span x-show="syncing">Syncing...</span>
                                </button>
                            </div>
                        </div>
                        @endif

                        @if(in_array('talabat', $location->platforms ?? []))
                        <!-- Talabat Card -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-6 h-6 text-orange-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">Talabat</h4>
                                        @if($location->talabat_vendor_id)
                                            <p class="text-xs text-gray-500">Vendor ID: {{ $location->talabat_vendor_id }}</p>
                                        @else
                                            <p class="text-xs text-red-600">Vendor ID not configured</p>
                                        @endif
                                    </div>
                                </div>
                                @if(isset($location->platform_sync_status['talabat']['status']))
                                    @if($location->platform_sync_status['talabat']['status'] === 'success')
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Synced</span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Error</span>
                                    @endif
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Not Synced</span>
                                @endif
                            </div>

                            @if(isset($location->platform_sync_status['talabat']['last_sync']))
                                <p class="text-xs text-gray-500 mb-3">
                                    Last synced: {{ \Carbon\Carbon::parse($location->platform_sync_status['talabat']['last_sync'])->diffForHumans() }}
                                </p>
                            @endif

                            <div class="flex gap-2">
                                <button @click="syncStatus('talabat')"
                                        :disabled="syncing"
                                        class="flex-1 px-3 py-2 text-sm bg-orange-600 text-white rounded hover:bg-orange-700 disabled:opacity-50">
                                    <span x-show="!syncing">Sync Status</span>
                                    <span x-show="syncing">Syncing...</span>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                <strong>Note:</strong> Operating hours for Talabat are managed via catalog API.
                            </p>
                        </div>
                        @endif
                    </div>

                    <!-- Current Status Display -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-2">Current Status</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Active:</span>
                                <span class="font-medium ml-2">{{ $location->is_active ? 'Yes' : 'No' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Busy:</span>
                                <span class="font-medium ml-2">{{ $location->is_busy ? 'Yes' : 'No' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sync Results Display -->
                    <div x-show="syncResults" x-cloak class="mt-4 p-4 rounded-lg"
                         :class="syncSuccess ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                        <p class="text-sm font-medium" :class="syncSuccess ? 'text-green-800' : 'text-red-800'" x-text="syncMessage"></p>
                    </div>
                </div>
            </div>

            <script>
                function locationPlatformSync() {
                    return {
                        syncing: false,
                        syncResults: false,
                        syncSuccess: false,
                        syncMessage: '',

                        async syncStatus(platform) {
                            this.syncing = true;
                            this.syncResults = false;

                            try {
                                const response = await fetch('{{ route("dashboard.locations.sync-status", ["location" => $location, "subdomain" => request()->route("subdomain")]) }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        platforms: [platform]
                                    })
                                });

                                const data = await response.json();
                                this.syncResults = true;
                                this.syncSuccess = data.success;
                                this.syncMessage = data.message;

                                if (data.success) {
                                    setTimeout(() => window.location.reload(), 1500);
                                }
                            } catch (error) {
                                this.syncResults = true;
                                this.syncSuccess = false;
                                this.syncMessage = 'Failed to sync: ' + error.message;
                            } finally {
                                this.syncing = false;
                            }
                        },

                        async syncHours(platform) {
                            this.syncing = true;
                            this.syncResults = false;

                            try {
                                const response = await fetch('{{ route("dashboard.locations.sync-hours", ["location" => $location, "subdomain" => request()->route("subdomain")]) }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        platforms: [platform]
                                    })
                                });

                                const data = await response.json();
                                this.syncResults = true;
                                this.syncSuccess = data.success;
                                this.syncMessage = data.message;

                                if (data.success) {
                                    setTimeout(() => window.location.reload(), 1500);
                                }
                            } catch (error) {
                                this.syncResults = true;
                                this.syncSuccess = false;
                                this.syncMessage = 'Failed to sync hours: ' + error.message;
                            } finally {
                                this.syncing = false;
                            }
                        }
                    }
                }
            </script>
            @endif
        </div>
    </div>
</x-app-layout>
