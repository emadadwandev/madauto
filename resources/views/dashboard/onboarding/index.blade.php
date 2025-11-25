<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-extrabold text-gray-900 mb-4">Welcome! Let's Get You Set Up</h1>
                <p class="text-xl text-gray-600">Follow these simple steps to start syncing your orders</p>
            </div>

            <!-- Progress Indicator - 6 Steps -->
            <div class="mb-12" x-data="{
                currentStep: {{ $onboardingStatus['account_configured'] ? ($onboardingStatus['location_created'] ? ($onboardingStatus['loyverse_connected'] ? ($onboardingStatus['careem_webhook_configured'] ? ($onboardingStatus['platform_apis_configured'] ? 6 : 5) : 4) : 3) : 2) : 1 }}
            }">
                <div class="flex items-center justify-between">
                    <!-- Step 1: Account Settings -->
                    <div class="flex-1">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div :class="currentStep >= 1 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600'" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm">
                                    <span x-show="!{{ $onboardingStatus['account_configured'] ? 'true' : 'false' }}">1</span>
                                    <svg x-show="{{ $onboardingStatus['account_configured'] ? 'true' : 'false' }}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-2 flex-1">
                                <p class="text-xs font-medium" :class="currentStep >= 1 ? 'text-indigo-600' : 'text-gray-500'">Account</p>
                            </div>
                        </div>
                    </div>

                    <!-- Connector -->
                    <div class="flex-1 px-1">
                        <div :class="currentStep >= 2 ? 'bg-indigo-600' : 'bg-gray-300'" class="h-1 w-full"></div>
                    </div>

                    <!-- Step 2: Location -->
                    <div class="flex-1">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div :class="currentStep >= 2 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600'" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm">
                                    <span x-show="!{{ $onboardingStatus['location_created'] ? 'true' : 'false' }}">2</span>
                                    <svg x-show="{{ $onboardingStatus['location_created'] ? 'true' : 'false' }}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-2 flex-1">
                                <p class="text-xs font-medium" :class="currentStep >= 2 ? 'text-indigo-600' : 'text-gray-500'">Location</p>
                            </div>
                        </div>
                    </div>

                    <!-- Connector -->
                    <div class="flex-1 px-1">
                        <div :class="currentStep >= 3 ? 'bg-indigo-600' : 'bg-gray-300'" class="h-1 w-full"></div>
                    </div>

                    <!-- Step 3: Loyverse -->
                    <div class="flex-1">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div :class="currentStep >= 3 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600'" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm">
                                    <span x-show="!{{ $onboardingStatus['loyverse_connected'] ? 'true' : 'false' }}">3</span>
                                    <svg x-show="{{ $onboardingStatus['loyverse_connected'] ? 'true' : 'false' }}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-2 flex-1">
                                <p class="text-xs font-medium" :class="currentStep >= 3 ? 'text-indigo-600' : 'text-gray-500'">Loyverse</p>
                            </div>
                        </div>
                    </div>

                    <!-- Connector -->
                    <div class="flex-1 px-1">
                        <div :class="currentStep >= 4 ? 'bg-indigo-600' : 'bg-gray-300'" class="h-1 w-full"></div>
                    </div>

                    <!-- Step 4: Careem -->
                    <div class="flex-1">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div :class="currentStep >= 4 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600'" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm">
                                    <span x-show="!{{ $onboardingStatus['careem_webhook_configured'] ? 'true' : 'false' }}">4</span>
                                    <svg x-show="{{ $onboardingStatus['careem_webhook_configured'] ? 'true' : 'false' }}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-2 flex-1">
                                <p class="text-xs font-medium" :class="currentStep >= 4 ? 'text-indigo-600' : 'text-gray-500'">Careem</p>
                            </div>
                        </div>
                    </div>

                    <!-- Connector -->
                    <div class="flex-1 px-1">
                        <div :class="currentStep >= 5 ? 'bg-indigo-600' : 'bg-gray-300'" class="h-1 w-full"></div>
                    </div>

                    <!-- Step 5: Platform APIs -->
                    <div class="flex-1">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div :class="currentStep >= 5 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600'" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm">
                                    <span x-show="!{{ $onboardingStatus['platform_apis_configured'] ? 'true' : 'false' }}">5</span>
                                    <svg x-show="{{ $onboardingStatus['platform_apis_configured'] ? 'true' : 'false' }}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-2 flex-1">
                                <p class="text-xs font-medium" :class="currentStep >= 5 ? 'text-indigo-600' : 'text-gray-500'">Platform</p>
                            </div>
                        </div>
                    </div>

                    <!-- Connector -->
                    <div class="flex-1 px-1">
                        <div :class="currentStep >= 6 ? 'bg-indigo-600' : 'bg-gray-300'" class="h-1 w-full"></div>
                    </div>

                    <!-- Step 6: Complete -->
                    <div class="flex-1">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div :class="currentStep >= 6 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600'" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm">
                                    <span>6</span>
                                </div>
                            </div>
                            <div class="ml-2 flex-1">
                                <p class="text-xs font-medium" :class="currentStep >= 6 ? 'text-indigo-600' : 'text-gray-500'">Done</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 1: Account Settings -->
            @if(!$onboardingStatus['account_configured'])
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Step 1: Account Settings</h2>
                            <p class="text-gray-600">Configure your currency and timezone preferences</p>
                        </div>
                    </div>

                    <form action="{{ route('dashboard.onboarding.account-settings.save', ['subdomain' => tenant()->subdomain]) }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Currency Selection -->
                            <div>
                                <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                                <select name="currency" id="currency" required class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Select Currency</option>
                                    @foreach($currencies as $code => $config)
                                        <option value="{{ $code }}" {{ old('currency', $tenant->getCurrency()) == $code ? 'selected' : '' }}>
                                            {{ $config['name'] }} ({{ $config['symbol'] }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('currency')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Timezone Selection -->
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                                <select name="timezone" id="timezone" required class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Select Timezone</option>
                                    @foreach($timezones as $tz => $label)
                                        <option value="{{ $tz }}" {{ old('timezone', $tenant->getTimezone()) == $tz ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('timezone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('dashboard.onboarding.skip', ['subdomain' => tenant()->subdomain]) }}" class="text-sm text-gray-600 hover:text-gray-900">Skip for now</a>
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-lg">
                                Continue
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <!-- Step 1 Complete -->
                <div class="bg-green-50 border-l-4 border-green-400 p-6 mb-8 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-green-800">Account Settings Configured!</h3>
                            <p class="text-sm text-green-700">Currency: {{ $tenant->getCurrency() }} | Timezone: {{ $tenant->getTimezone() }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 2: Location Creation -->
            @if($onboardingStatus['account_configured'] && !$onboardingStatus['location_created'])
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Step 2: Create Your First Location</h2>
                            <p class="text-gray-600">Add your restaurant or business location</p>
                        </div>
                    </div>

                    <form action="{{ route('dashboard.onboarding.location.save', ['subdomain' => tenant()->subdomain]) }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Location Name -->
                            <div class="md:col-span-2">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Location Name *</label>
                                <input type="text" name="name" id="name" required value="{{ old('name') }}"
                                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="e.g., Main Branch, Downtown Location">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Address Line 1 -->
                            <div class="md:col-span-2">
                                <label for="address_line1" class="block text-sm font-medium text-gray-700 mb-2">Address Line 1 *</label>
                                <input type="text" name="address_line1" id="address_line1" required value="{{ old('address_line1') }}"
                                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Street address">
                                @error('address_line1')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Address Line 2 -->
                            <div class="md:col-span-2">
                                <label for="address_line2" class="block text-sm font-medium text-gray-700 mb-2">Address Line 2</label>
                                <input type="text" name="address_line2" id="address_line2" value="{{ old('address_line2') }}"
                                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Apartment, suite, unit, building, floor, etc.">
                            </div>

                            <!-- City -->
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                                <input type="text" name="city" id="city" required value="{{ old('city') }}"
                                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                @error('city')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- State/Province -->
                            <div>
                                <label for="state" class="block text-sm font-medium text-gray-700 mb-2">State/Province</label>
                                <input type="text" name="state" id="state" value="{{ old('state') }}"
                                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <!-- Postal Code -->
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                                <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code') }}"
                                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <!-- Country -->
                            <div>
                                <label for="country" class="block text-sm font-medium text-gray-700 mb-2">Country *</label>
                                <select name="country" id="country" required class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="AE" {{ old('country', 'AE') == 'AE' ? 'selected' : '' }}>United Arab Emirates</option>
                                    <option value="SA" {{ old('country') == 'SA' ? 'selected' : '' }}>Saudi Arabia</option>
                                    <option value="KW" {{ old('country') == 'KW' ? 'selected' : '' }}>Kuwait</option>
                                    <option value="BH" {{ old('country') == 'BH' ? 'selected' : '' }}>Bahrain</option>
                                    <option value="QA" {{ old('country') == 'QA' ? 'selected' : '' }}>Qatar</option>
                                    <option value="OM" {{ old('country') == 'OM' ? 'selected' : '' }}>Oman</option>
                                    <option value="JO" {{ old('country') == 'JO' ? 'selected' : '' }}>Jordan</option>
                                    <option value="EG" {{ old('country') == 'EG' ? 'selected' : '' }}>Egypt</option>
                                </select>
                                @error('country')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}"
                                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <!-- Platforms -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Connected Platforms *</label>
                                <div class="flex gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="platforms[]" value="careem" {{ in_array('careem', old('platforms', ['careem'])) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <span class="ml-2">Careem NOW</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="platforms[]" value="talabat" {{ in_array('talabat', old('platforms', [])) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <span class="ml-2">Talabat</span>
                                    </label>
                                </div>
                                @error('platforms')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Platform IDs Section (Optional) -->
                        <div class="border-t border-gray-200 pt-6 mb-6">
                            <div class="mb-4">
                                <h3 class="text-sm font-semibold text-gray-900 mb-1">Platform Store IDs <span class="text-gray-500 font-normal">(Optional - for advanced features)</span></h3>
                                <p class="text-xs text-gray-600">These IDs enable location status syncing and menu publishing. You can add them now or later in Settings.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Loyverse Store ID -->
                                <div>
                                    <label for="loyverse_store_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Loyverse Store ID
                                    </label>
                                    <input type="text" name="loyverse_store_id" id="loyverse_store_id" value="{{ old('loyverse_store_id') }}"
                                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Optional">
                                    <p class="mt-1 text-xs text-gray-500">Find in Loyverse Back Office</p>
                                    @error('loyverse_store_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Careem Store ID -->
                                <div>
                                    <label for="careem_store_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Careem Store ID
                                    </label>
                                    <input type="text" name="careem_store_id" id="careem_store_id" value="{{ old('careem_store_id') }}"
                                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Optional">
                                    <p class="mt-1 text-xs text-gray-500">Find in Careem Partner Portal</p>
                                    @error('careem_store_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Talabat Vendor ID -->
                                <div>
                                    <label for="talabat_vendor_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Talabat Vendor ID
                                    </label>
                                    <input type="text" name="talabat_vendor_id" id="talabat_vendor_id" value="{{ old('talabat_vendor_id') }}"
                                        class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Optional">
                                    <p class="mt-1 text-xs text-gray-500">Find in Delivery Hero Portal</p>
                                    @error('talabat_vendor_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('dashboard.onboarding.skip', ['subdomain' => tenant()->subdomain]) }}" class="text-sm text-gray-600 hover:text-gray-900">Skip for now</a>
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition-colors shadow-lg">
                                Continue
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            @elseif($onboardingStatus['location_created'])
                <!-- Step 2 Complete -->
                <div class="bg-green-50 border-l-4 border-green-400 p-6 mb-8 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-green-800">Location Created!</h3>
                            <p class="text-sm text-green-700">Your first location has been successfully added.</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 3: Connect Loyverse -->
            @if($onboardingStatus['account_configured'] && $onboardingStatus['location_created'] && !$onboardingStatus['loyverse_connected'])
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Step 3: Connect Your Loyverse Account</h2>
                            <p class="text-gray-600">Enter your Loyverse API token to enable order syncing</p>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-semibold text-blue-800">How to get your Loyverse API token:</h3>
                                <ol class="mt-2 text-sm text-blue-700 list-decimal list-inside space-y-1">
                                    <li>Log in to your <a href="https://loyverse.com/dashboard" target="_blank" class="underline">Loyverse Back Office</a></li>
                                    <li>Go to <strong>Settings → API tokens</strong></li>
                                    <li>Click <strong>"Create New Token"</strong></li>
                                    <li>Give it a name (e.g., "Careem Integration")</li>
                                    <li>Copy the token and paste it below</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <form action="{{ route('dashboard.onboarding.loyverse.save', ['subdomain' => tenant()->subdomain]) }}" method="POST">
                        @csrf

                        <div class="mb-6">
                            <label for="api_token" class="block text-sm font-medium text-gray-700 mb-2">Loyverse API Token</label>
                            <input
                                type="text"
                                name="api_token"
                                id="api_token"
                                required
                                class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Enter your Loyverse API token"
                            >
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('dashboard.onboarding.skip', ['subdomain' => tenant()->subdomain]) }}" class="text-sm text-gray-600 hover:text-gray-900">Skip for now</a>
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors shadow-lg">
                                Connect Loyverse
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            @elseif($onboardingStatus['loyverse_connected'])
                <!-- Step 3 Complete -->
                <div class="bg-green-50 border-l-4 border-green-400 p-6 mb-8 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-green-800">Loyverse Connected!</h3>
                            <p class="text-sm text-green-700">Your Loyverse account is successfully connected and ready to receive orders.</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 4: Configure Careem Webhook -->
            @if($onboardingStatus['account_configured'] && $onboardingStatus['location_created'] && $onboardingStatus['loyverse_connected'] && !$onboardingStatus['careem_webhook_configured'])
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Step 4: Configure Careem Webhook</h2>
                            <p class="text-gray-600">Set up the webhook to receive orders from Careem Now</p>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-semibold text-green-800">What happens next:</h3>
                                <ol class="mt-2 text-sm text-green-700 list-decimal list-inside space-y-1">
                                    <li>Click "Generate Webhook Secret" to create your secure webhook credentials</li>
                                    <li>Copy the Webhook URL and Secret that will be displayed</li>
                                    <li>Contact Careem support to add these credentials to your Careem Now account</li>
                                    <li>Once configured, orders will automatically sync from Careem to Loyverse!</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Generate Button -->
                    <form action="{{ route('dashboard.onboarding.webhook.generate', ['subdomain' => tenant()->subdomain]) }}" method="POST">
                        @csrf

                        <div class="flex items-center justify-between">
                            <a href="{{ route('dashboard.onboarding.skip', ['subdomain' => tenant()->subdomain]) }}" class="text-sm text-gray-600 hover:text-gray-900">Skip for now</a>
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors shadow-lg">
                                Generate Webhook Secret
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            @elseif($onboardingStatus['careem_webhook_configured'])
                <!-- Step 4 Complete -->
                <div class="bg-green-50 border-l-4 border-green-400 p-6 mb-8 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-green-800">Careem Webhook Configured!</h3>
                            <p class="text-sm text-green-700 mb-3">Your webhook credentials have been generated. Here are your details:</p>

                            <div class="bg-white rounded-lg p-4 mt-2">
                                <div class="mb-3">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Webhook URL:</label>
                                    <div class="flex items-center">
                                        <code class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded text-sm">
                                            {{ route('api.webhook.careem') }}
                                        </code>
                                        <button onclick="copyToClipboard('{{ route('api.webhook.careem') }}')" class="ml-2 px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded text-sm">
                                            Copy
                                        </button>
                                    </div>
                                </div>

                                @if($careemWebhookCredential)
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Webhook Secret:</label>
                                        <div class="flex items-center">
                                            <code class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded text-sm">
                                                {{ $careemWebhookCredential->credentials['webhook_secret'] ?? 'N/A' }}
                                            </code>
                                            <button onclick="copyToClipboard('{{ $careemWebhookCredential->credentials['webhook_secret'] ?? '' }}')" class="ml-2 px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded text-sm">
                                                Copy
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <p class="text-sm text-green-700 mt-3">
                                <strong>Next:</strong> Contact Careem support to add these credentials to your account.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 5: Platform API Credentials (Optional) -->
            @if($onboardingStatus['account_configured'] && $onboardingStatus['location_created'] && $onboardingStatus['loyverse_connected'] && $onboardingStatus['careem_webhook_configured'] && !$onboardingStatus['platform_apis_configured'])
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Step 5: Platform API Credentials <span class="text-base font-normal text-gray-500">(Optional)</span></h2>
                            <p class="text-gray-600">Configure API access for menu publishing and location sync</p>
                        </div>
                    </div>

                    <!-- Benefits Box -->
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-semibold text-yellow-800">Why configure API credentials?</h3>
                                <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside space-y-1">
                                    <li><strong>Menu Publishing:</strong> Automatically sync your menus to Careem NOW and Talabat</li>
                                    <li><strong>Location Status Sync:</strong> Control store hours and busy/active status remotely</li>
                                    <li><strong>Real-time Updates:</strong> Keep item availability in sync across all platforms</li>
                                </ul>
                                <p class="mt-2 text-sm text-yellow-700 font-medium">
                                    ⚠️ Skip this step to receive orders only. You can configure these later in Settings → API Credentials.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Careem Catalog API Form -->
                    <div class="mb-8" x-data="{ expanded: false }">
                        <button @click="expanded = !expanded" type="button" class="w-full flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <h3 class="font-semibold text-gray-900">Careem Catalog API</h3>
                                    <p class="text-sm text-gray-600">For menu publishing and store management</p>
                                </div>
                            </div>
                            <svg :class="{ 'rotate-180': expanded }" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="expanded" x-collapse class="mt-4">
                            <form action="{{ route('dashboard.onboarding.careem-catalog.save', ['subdomain' => tenant()->subdomain]) }}" method="POST" class="bg-gray-50 p-6 rounded-lg">
                                @csrf

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="careem_client_id" class="block text-sm font-medium text-gray-700 mb-2">Client ID *</label>
                                        <input type="text" name="client_id" id="careem_client_id" required
                                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Your Careem OAuth2 Client ID"
                                            value="{{ old('client_id', $careemCatalogCredential->credentials['client_id'] ?? '') }}">
                                    </div>

                                    <div>
                                        <label for="careem_client_secret" class="block text-sm font-medium text-gray-700 mb-2">Client Secret *</label>
                                        <input type="password" name="client_secret" id="careem_client_secret" required
                                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Your Careem OAuth2 Client Secret">
                                    </div>
                                </div>

                                <p class="text-xs text-gray-600 mb-4">
                                    💡 Get these credentials from your <a href="https://partner.careemnow.com" target="_blank" class="text-indigo-600 hover:underline">Careem Partner Portal</a>
                                </p>

                                <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors shadow-lg">
                                    Save Careem Credentials
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Talabat API Form -->
                    <div class="mb-6" x-data="{ expanded: false }">
                        <button @click="expanded = !expanded" type="button" class="w-full flex items-center justify-between p-4 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 transition-colors">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-6 h-6 text-orange-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <h3 class="font-semibold text-gray-900">Talabat API</h3>
                                    <p class="text-sm text-gray-600">For menu publishing and vendor management</p>
                                </div>
                            </div>
                            <svg :class="{ 'rotate-180': expanded }" class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="expanded" x-collapse class="mt-4">
                            <form action="{{ route('dashboard.onboarding.talabat.save', ['subdomain' => tenant()->subdomain]) }}" method="POST" class="bg-gray-50 p-6 rounded-lg">
                                @csrf

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label for="talabat_client_id" class="block text-sm font-medium text-gray-700 mb-2">Client ID *</label>
                                        <input type="text" name="client_id" id="talabat_client_id" required
                                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Your Talabat OAuth2 Client ID"
                                            value="{{ old('client_id', $talabatCredential->credentials['client_id'] ?? '') }}">
                                    </div>

                                    <div>
                                        <label for="talabat_client_secret" class="block text-sm font-medium text-gray-700 mb-2">Client Secret *</label>
                                        <input type="password" name="client_secret" id="talabat_client_secret" required
                                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Your Talabat OAuth2 Client Secret">
                                    </div>

                                    <div class="md:col-span-2">
                                        <label for="talabat_chain_code" class="block text-sm font-medium text-gray-700 mb-2">Chain Code *</label>
                                        <input type="text" name="chain_code" id="talabat_chain_code" required
                                            class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Your restaurant chain identifier"
                                            value="{{ old('chain_code', $talabatCredential->credentials['chain_code'] ?? '') }}">
                                    </div>
                                </div>

                                <p class="text-xs text-gray-600 mb-4">
                                    💡 Get these credentials from your <a href="https://partner.deliveryhero.com" target="_blank" class="text-indigo-600 hover:underline">Delivery Hero Portal</a>
                                </p>

                                <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700 transition-colors shadow-lg">
                                    Save Talabat Credentials
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <a href="{{ route('dashboard.onboarding.skip', ['subdomain' => tenant()->subdomain]) }}" class="text-sm text-gray-600 hover:text-gray-900">
                            Skip - Configure later in Settings
                        </a>
                        <form action="{{ route('dashboard.onboarding.complete', ['subdomain' => tenant()->subdomain]) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition-colors shadow-lg">
                                Complete Without API Credentials
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            @elseif($onboardingStatus['platform_apis_configured'])
                <!-- Step 5 Complete -->
                <div class="bg-green-50 border-l-4 border-green-400 p-6 mb-8 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-semibold text-green-800">Platform API Credentials Configured!</h3>
                            <p class="text-sm text-green-700">You can now publish menus and sync location status to delivery platforms.</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 6: Complete Onboarding -->
            @if($onboardingStatus['account_configured'] && $onboardingStatus['location_created'] && $onboardingStatus['loyverse_connected'] && $onboardingStatus['careem_webhook_configured'])
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full mb-4">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>

                        <h2 class="text-3xl font-extrabold text-gray-900 mb-4">You're All Set!</h2>
                        <p class="text-lg text-gray-600 mb-8">Your integration is configured and ready to sync orders automatically.</p>

                        <form action="{{ route('dashboard.onboarding.complete', ['subdomain' => tenant()->subdomain]) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                Go to Dashboard
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard!');
            });
        }
    </script>
    @endpush
</x-app-layout>
