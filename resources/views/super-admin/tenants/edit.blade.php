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

                    <!-- Additional Settings -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Settings</h3>
                        <div class="space-y-4">
                            <!-- Settings JSON (optional advanced field) -->
                            <div>
                                <label for="settings" class="block text-sm font-medium text-gray-700">
                                    Settings (JSON)
                                </label>
                                <textarea name="settings" id="settings" rows="4"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono text-xs"
                                          placeholder='{"key": "value"}'>{{ old('settings', json_encode($tenant->settings ?? [], JSON_PRETTY_PRINT)) }}</textarea>
                                <p class="mt-1 text-sm text-gray-500">Advanced: Custom JSON settings for this tenant</p>
                                @error('settings')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
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
