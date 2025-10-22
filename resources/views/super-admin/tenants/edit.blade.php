<x-super-admin-layout>
    <div class="space-y-6">
        <!-- Page Header -->
        <div>
            <div class="flex items-center">
                <a href="{{ route('super-admin.tenants.show', $tenant) }}" class="mr-3 text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-2xl font-semibold text-gray-900">Edit Tenant</h1>
            </div>
            <p class="mt-1 text-sm text-gray-500">Update tenant information and settings</p>
        </div>

        <!-- Edit Form -->
        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('super-admin.tenants.update', $tenant) }}">
                @csrf
                @method('PUT')

                <div class="px-4 py-5 sm:p-6 space-y-6">
                    <!-- Basic Information -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">
                                    Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name', $tenant->name) }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('name') border-red-300 @enderror">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" name="email" id="email" value="{{ old('email', $tenant->email) }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('email') border-red-300 @enderror">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Subdomain -->
                            <div>
                                <label for="subdomain" class="block text-sm font-medium text-gray-700">
                                    Subdomain <span class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <input type="text" name="subdomain" id="subdomain" value="{{ old('subdomain', $tenant->subdomain) }}" required
                                           class="flex-1 block w-full rounded-l-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('subdomain') border-red-300 @enderror">
                                    <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                        .yourapp.com
                                    </span>
                                </div>
                                @error('subdomain')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Custom Domain (Optional) -->
                            <div>
                                <label for="domain" class="block text-sm font-medium text-gray-700">
                                    Custom Domain
                                </label>
                                <input type="text" name="domain" id="domain" value="{{ old('domain', $tenant->domain ?? '') }}"
                                       placeholder="example.com"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('domain') border-red-300 @enderror">
                                <p class="mt-1 text-sm text-gray-500">Optional: Custom domain for this tenant</p>
                                @error('domain')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">
                                    Status
                                </label>
                                <select name="status" id="status"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="active" {{ old('status', $tenant->status) === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="suspended" {{ old('status', $tenant->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                    <option value="trialing" {{ old('status', $tenant->status) === 'trialing' ? 'selected' : '' }}>Trialing</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Trial Settings -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Trial Settings</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <!-- Trial Ends At -->
                            <div>
                                <label for="trial_ends_at" class="block text-sm font-medium text-gray-700">
                                    Trial Ends At
                                </label>
                                <input type="date" name="trial_ends_at" id="trial_ends_at"
                                       value="{{ old('trial_ends_at', $tenant->trial_ends_at?->format('Y-m-d')) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <p class="mt-1 text-sm text-gray-500">Leave empty if not in trial</p>
                            </div>
                        </div>
                    </div>

                    <!-- Onboarding -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Onboarding</h3>
                        <div class="flex items-center">
                            <input type="hidden" name="onboarding_completed" value="0">
                            <input type="checkbox" name="onboarding_completed" id="onboarding_completed" value="1"
                                   {{ old('onboarding_completed', $tenant->onboarding_completed) ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="onboarding_completed" class="ml-2 block text-sm text-gray-900">
                                Mark onboarding as completed
                            </label>
                        </div>
                    </div>

                    <!-- Platform Settings -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Platform Settings</h3>

                        <!-- Enabled Platforms -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Enabled Platforms
                            </label>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <input type="checkbox" name="enabled_platforms[]" id="platform_careem" value="careem"
                                           {{ in_array('careem', old('enabled_platforms', $tenant->settings['enabled_platforms'] ?? [])) ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="platform_careem" class="ml-2 block text-sm text-gray-900">
                                        Careem NOW
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="enabled_platforms[]" id="platform_talabat" value="talabat"
                                           {{ in_array('talabat', old('enabled_platforms', $tenant->settings['enabled_platforms'] ?? [])) ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="platform_talabat" class="ml-2 block text-sm text-gray-900">
                                        Talabat
                                    </label>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Select which platforms this tenant will integrate with</p>
                        </div>

                        <!-- Auto Accept Settings -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Auto-Accept Orders
                            </label>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <input type="checkbox" name="auto_accept_careem" id="auto_accept_careem" value="1"
                                           {{ old('auto_accept_careem', $tenant->settings['auto_accept_careem'] ?? false) ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="auto_accept_careem" class="ml-2 block text-sm text-gray-900">
                                        Auto-accept Careem orders
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="auto_accept_talabat" id="auto_accept_talabat" value="1"
                                           {{ old('auto_accept_talabat', $tenant->settings['auto_accept_talabat'] ?? false) ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="auto_accept_talabat" class="ml-2 block text-sm text-gray-900">
                                        Auto-accept Talabat orders
                                    </label>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">When enabled, orders will be automatically accepted and synced to Loyverse</p>
                        </div>
                    </div>

                    <!-- Additional Settings -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Settings</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <!-- Timezone -->
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700">
                                    Timezone
                                </label>
                                <select name="timezone" id="timezone"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="UTC" {{ old('timezone', $tenant->settings['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : '' }}>UTC (Coordinated Universal Time)</option>
                                    <optgroup label="Gulf Countries">
                                        <option value="Asia/Dubai" {{ old('timezone', $tenant->settings['timezone'] ?? 'Asia/Dubai') === 'Asia/Dubai' ? 'selected' : '' }}>UAE - Dubai (UTC+4)</option>
                                        <option value="Asia/Riyadh" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Asia/Riyadh' ? 'selected' : '' }}>Saudi Arabia - Riyadh (UTC+3)</option>
                                        <option value="Asia/Kuwait" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Asia/Kuwait' ? 'selected' : '' }}>Kuwait (UTC+3)</option>
                                        <option value="Asia/Qatar" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Asia/Qatar' ? 'selected' : '' }}>Qatar (UTC+3)</option>
                                        <option value="Asia/Bahrain" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Asia/Bahrain' ? 'selected' : '' }}>Bahrain (UTC+3)</option>
                                        <option value="Asia/Muscat" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Asia/Muscat' ? 'selected' : '' }}>Oman - Muscat (UTC+4)</option>
                                    </optgroup>
                                    <optgroup label="Levant Countries">
                                        <option value="Asia/Amman" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Asia/Amman' ? 'selected' : '' }}>Jordan - Amman (UTC+2)</option>
                                        <option value="Asia/Beirut" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Asia/Beirut' ? 'selected' : '' }}>Lebanon - Beirut (UTC+2)</option>
                                        <option value="Asia/Damascus" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Asia/Damascus' ? 'selected' : '' }}>Syria - Damascus (UTC+2)</option>
                                        <option value="Asia/Gaza" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Asia/Gaza' ? 'selected' : '' }}>Palestine - Gaza (UTC+2)</option>
                                        <option value="Asia/Hebron" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Asia/Hebron' ? 'selected' : '' }}>Palestine - Hebron (UTC+2)</option>
                                    </optgroup>
                                    <optgroup label="Iraq & Yemen">
                                        <option value="Asia/Baghdad" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Asia/Baghdad' ? 'selected' : '' }}>Iraq - Baghdad (UTC+3)</option>
                                        <option value="Asia/Aden" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Asia/Aden' ? 'selected' : '' }}>Yemen - Aden (UTC+3)</option>
                                    </optgroup>
                                    <optgroup label="North Africa">
                                        <option value="Africa/Cairo" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Africa/Cairo' ? 'selected' : '' }}>Egypt - Cairo (UTC+2)</option>
                                        <option value="Africa/Tripoli" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Africa/Tripoli' ? 'selected' : '' }}>Libya - Tripoli (UTC+2)</option>
                                        <option value="Africa/Tunis" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Africa/Tunis' ? 'selected' : '' }}>Tunisia - Tunis (UTC+1)</option>
                                        <option value="Africa/Algiers" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Africa/Algiers' ? 'selected' : '' }}>Algeria - Algiers (UTC+1)</option>
                                        <option value="Africa/Casablanca" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Africa/Casablanca' ? 'selected' : '' }}>Morocco - Casablanca (UTC+1)</option>
                                    </optgroup>
                                    <optgroup label="East & Horn of Africa">
                                        <option value="Africa/Khartoum" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Africa/Khartoum' ? 'selected' : '' }}>Sudan - Khartoum (UTC+2)</option>
                                        <option value="Africa/Mogadishu" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Africa/Mogadishu' ? 'selected' : '' }}>Somalia - Mogadishu (UTC+3)</option>
                                        <option value="Africa/Djibouti" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Africa/Djibouti' ? 'selected' : '' }}>Djibouti (UTC+3)</option>
                                        <option value="Indian/Comoro" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Indian/Comoro' ? 'selected' : '' }}>Comoros (UTC+3)</option>
                                    </optgroup>
                                    <optgroup label="West Africa">
                                        <option value="Africa/Nouakchott" {{ old('timezone', $tenant->settings['timezone'] ?? '') === 'Africa/Nouakchott' ? 'selected' : '' }}>Mauritania - Nouakchott (UTC+0)</option>
                                    </optgroup>
                                </select>
                            </div>

                            <!-- Currency -->
                            <div>
                                <label for="currency" class="block text-sm font-medium text-gray-700">
                                    Currency
                                </label>
                                <select name="currency" id="currency"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="USD" {{ old('currency', $tenant->settings['currency'] ?? '') === 'USD' ? 'selected' : '' }}>USD - US Dollar ($)</option>
                                    <optgroup label="Gulf Countries">
                                        <option value="AED" {{ old('currency', $tenant->settings['currency'] ?? 'AED') === 'AED' ? 'selected' : '' }}>AED - UAE Dirham (د.إ)</option>
                                        <option value="SAR" {{ old('currency', $tenant->settings['currency'] ?? '') === 'SAR' ? 'selected' : '' }}>SAR - Saudi Riyal (﷼)</option>
                                        <option value="KWD" {{ old('currency', $tenant->settings['currency'] ?? '') === 'KWD' ? 'selected' : '' }}>KWD - Kuwaiti Dinar (د.ك)</option>
                                        <option value="QAR" {{ old('currency', $tenant->settings['currency'] ?? '') === 'QAR' ? 'selected' : '' }}>QAR - Qatari Riyal (﷼)</option>
                                        <option value="BHD" {{ old('currency', $tenant->settings['currency'] ?? '') === 'BHD' ? 'selected' : '' }}>BHD - Bahraini Dinar (د.ب)</option>
                                        <option value="OMR" {{ old('currency', $tenant->settings['currency'] ?? '') === 'OMR' ? 'selected' : '' }}>OMR - Omani Rial (ر.ع.)</option>
                                    </optgroup>
                                    <optgroup label="Levant Countries">
                                        <option value="JOD" {{ old('currency', $tenant->settings['currency'] ?? '') === 'JOD' ? 'selected' : '' }}>JOD - Jordanian Dinar (د.ا)</option>
                                        <option value="LBP" {{ old('currency', $tenant->settings['currency'] ?? '') === 'LBP' ? 'selected' : '' }}>LBP - Lebanese Pound (ل.ل)</option>
                                        <option value="SYP" {{ old('currency', $tenant->settings['currency'] ?? '') === 'SYP' ? 'selected' : '' }}>SYP - Syrian Pound (£S)</option>
                                        <option value="ILS" {{ old('currency', $tenant->settings['currency'] ?? '') === 'ILS' ? 'selected' : '' }}>ILS - Israeli Shekel (₪) [Palestine]</option>
                                    </optgroup>
                                    <optgroup label="Iraq & Yemen">
                                        <option value="IQD" {{ old('currency', $tenant->settings['currency'] ?? '') === 'IQD' ? 'selected' : '' }}>IQD - Iraqi Dinar (ع.د)</option>
                                        <option value="YER" {{ old('currency', $tenant->settings['currency'] ?? '') === 'YER' ? 'selected' : '' }}>YER - Yemeni Rial (﷼)</option>
                                    </optgroup>
                                    <optgroup label="North Africa">
                                        <option value="EGP" {{ old('currency', $tenant->settings['currency'] ?? '') === 'EGP' ? 'selected' : '' }}>EGP - Egyptian Pound (ج.م)</option>
                                        <option value="LYD" {{ old('currency', $tenant->settings['currency'] ?? '') === 'LYD' ? 'selected' : '' }}>LYD - Libyan Dinar (ل.د)</option>
                                        <option value="TND" {{ old('currency', $tenant->settings['currency'] ?? '') === 'TND' ? 'selected' : '' }}>TND - Tunisian Dinar (د.ت)</option>
                                        <option value="DZD" {{ old('currency', $tenant->settings['currency'] ?? '') === 'DZD' ? 'selected' : '' }}>DZD - Algerian Dinar (د.ج)</option>
                                        <option value="MAD" {{ old('currency', $tenant->settings['currency'] ?? '') === 'MAD' ? 'selected' : '' }}>MAD - Moroccan Dirham (د.م.)</option>
                                    </optgroup>
                                    <optgroup label="East & Horn of Africa">
                                        <option value="SDG" {{ old('currency', $tenant->settings['currency'] ?? '') === 'SDG' ? 'selected' : '' }}>SDG - Sudanese Pound (ج.س)</option>
                                        <option value="SOS" {{ old('currency', $tenant->settings['currency'] ?? '') === 'SOS' ? 'selected' : '' }}>SOS - Somali Shilling (Sh.So.)</option>
                                        <option value="DJF" {{ old('currency', $tenant->settings['currency'] ?? '') === 'DJF' ? 'selected' : '' }}>DJF - Djiboutian Franc (Fdj)</option>
                                        <option value="KMF" {{ old('currency', $tenant->settings['currency'] ?? '') === 'KMF' ? 'selected' : '' }}>KMF - Comorian Franc (CF)</option>
                                    </optgroup>
                                    <optgroup label="West Africa">
                                        <option value="MRU" {{ old('currency', $tenant->settings['currency'] ?? '') === 'MRU' ? 'selected' : '' }}>MRU - Mauritanian Ouguiya (UM)</option>
                                    </optgroup>
                                </select>
                            </div>

                            <!-- Language -->
                            <div>
                                <label for="language" class="block text-sm font-medium text-gray-700">
                                    Language
                                </label>
                                <select name="language" id="language"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <option value="en" {{ old('language', $tenant->settings['language'] ?? 'en') === 'en' ? 'selected' : '' }}>English</option>
                                    <option value="ar" {{ old('language', $tenant->settings['language'] ?? '') === 'ar' ? 'selected' : '' }}>Arabic (العربية)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Notification Settings</h3>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <input type="checkbox" name="notify_on_new_order" id="notify_on_new_order" value="1"
                                       {{ old('notify_on_new_order', $tenant->settings['notify_on_new_order'] ?? true) ? 'checked' : '' }}
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="notify_on_new_order" class="ml-2 block text-sm text-gray-900">
                                    Notify on new orders
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="notify_on_failed_sync" id="notify_on_failed_sync" value="1"
                                       {{ old('notify_on_failed_sync', $tenant->settings['notify_on_failed_sync'] ?? true) ? 'checked' : '' }}
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="notify_on_failed_sync" class="ml-2 block text-sm text-gray-900">
                                    Notify on failed order sync
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="notify_on_usage_limit" id="notify_on_usage_limit" value="1"
                                       {{ old('notify_on_usage_limit', $tenant->settings['notify_on_usage_limit'] ?? true) ? 'checked' : '' }}
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="notify_on_usage_limit" class="ml-2 block text-sm text-gray-900">
                                    Notify when approaching usage limits
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex items-center justify-end gap-3">
                    <a href="{{ route('super-admin.tenants.show', $tenant) }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-super-admin-layout>
