<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notification Settings') }}
        </h2>
    </x-slot>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Notification Settings</h1>
            <p class="mt-1 text-sm text-gray-500">
                Configure email notifications for your restaurant
            </p>
        </div>

        <form method="POST" action="{{ route('dashboard.notifications.update', ['subdomain' => request()->route('subdomain')]) }}">
            @csrf

            <!-- Email Notifications -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md mb-6">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Email Notifications</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Choose which events should trigger email notifications
                    </p>
                </div>
                <div class="px-4 py-5 sm:px-6 space-y-6">
                    <!-- Failed Orders -->
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="failed_orders" class="text-sm font-medium text-gray-900">Failed Orders</label>
                            <p class="text-sm text-gray-500">Get notified when orders fail to sync with Loyverse</p>
                        </div>
                        <input type="checkbox" 
                               id="failed_orders" 
                               name="notifications[failed_orders]"
                               value="1"
                               {{ tenant()->settings['notifications']['failed_orders'] ?? 'true' ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    </div>

                    <!-- Usage Limits -->
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="usage_limits" class="text-sm font-medium text-gray-900">Usage Limits</label>
                            <p class="text-sm text-gray-500">Alert when approaching order limits (80% and 100%)</p>
                        </div>
                        <input type="checkbox" 
                               id="usage_limits" 
                               name="notifications[usage_limits]"
                               value="1"
                               {{ tenant()->settings['notifications']['usage_limits'] ?? 'true' ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    </div>

                    <!-- Payment Failures -->
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="payment_failures" class="text-sm font-medium text-gray-900">Payment Failures</label>
                            <p class="text-sm text-gray-500">Notify about subscription payment issues</p>
                        </div>
                        <input type="checkbox" 
                               id="payment_failures" 
                               name="notifications[payment_failures]"
                               value="1"
                               {{ tenant()->settings['notifications']['payment_failures'] ?? 'true' ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    </div>

                    <!-- New Team Members -->
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="team_members" class="text-sm font-medium text-gray-900">New Team Members</label>
                            <p class="text-sm text-gray-500">When someone accepts invitation to join the team</p>
                        </div>
                        <input type="checkbox" 
                               id="team_members" 
                               name="notifications[team_members]"
                               value="1"
                               {{ tenant()->settings['notifications']['team_members'] ?? 'true' ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    </div>

                    <!-- Weekly Summary -->
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="weekly_summary" class="text-sm font-medium text-gray-900">Weekly Summary</label>
                            <p class="text-sm text-gray-500">Weekly report of orders, team activity, and account usage</p>
                        </div>
                        <input type="checkbox" 
                               id="weekly_summary" 
                               name="notifications[weekly_summary]"
                               value="1"
                               {{ tenant()->settings['notifications']['weekly_summary'] ?? 'true' ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    </div>

                    <!-- System Updates -->
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="system_updates" class="text-sm font-medium text-gray-900">System Updates</label>
                            <p class="text-sm text-gray-500">Important platform updates and maintenance notifications</p>
                        </div>
                        <input type="checkbox" 
                               id="system_updates" 
                               name="notifications[system_updates]"
                               value="1"
                               {{ tenant()->settings['notifications']['system_updates'] ?? 'true' ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    </div>
                </div>
            </div>

            <!-- Recipient Settings -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md mb-6">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Notification Recipients</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Choose who receives email notifications
                    </p>
                </div>
                <div class="px-4 py-5 sm:px-6 space-y-4">
                    <!-- Admins Only -->
                    <div>
                        <label class="flex items-center">
                            <input type="radio" 
                                   id="admins_only"
                                   name="recipients"
                                   value="admins_only"
                                   {{ (tenant()->settings['notification_recipients'] ?? 'admins_only') === 'admins_only' ? 'checked' : '' }}
                                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                            <span class="ml-3">
                                <span class="text-sm font-medium text-gray-900">Admins Only</span>
                                <span class="text-sm text-gray-500 block">Send notifications to tenant admins only</span>
                            </span>
                        </label>
                    </div>

                    <!-- All Team Members -->
                    <div>
                        <label class="flex items-center">
                            <input type="radio" 
                                   id="all_members"
                                   name="recipients"
                                   value="all_members"
                                   {{ (tenant()->settings['notification_recipients'] ?? 'admins_only') === 'all_members' ? 'checked' : '' }}
                                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                            <span class="ml-3">
                                <span class="text-sm font-medium text-gray-900">All Team Members</span>
                                <span class="text-sm text-gray-500 block">Send notifications to all team members</span>
                            </span>
                        </label>
                    </div>

                    <!-- Custom Recipients -->
                    <div>
                        <label class="flex items-center">
                            <input type="radio" 
                                   id="custom"
                                   name="recipients"
                                   value="custom"
                                   {{ (tenant()->settings['notification_recipients'] ?? 'admins_only') === 'custom' ? 'checked' : '' }}
                                   class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                            <span class="ml-3">
                                <span class="text-sm font-medium text-gray-900">Custom Recipients</span>
                                <span class="text-sm text-gray-500 block">Specify custom email addresses</span>
                            </span>
                        </label>
                    </div>

                    <!-- Custom Emails (shown when custom is selected) -->
                    <div id="customEmails" class="mt-4 {{ (tenant()->settings['notification_recipients'] ?? 'admins_only') !== 'custom' ? 'hidden' : '' }}">
                        <label for="custom_emails" class="block text-sm font-medium text-gray-700 mb-2">
                            Email Addresses
                        </label>
                        <textarea 
                            id="custom_emails" 
                            name="custom_emails"
                            rows="3"
                            class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border-gray-300 rounded-md"
                            placeholder="Enter email addresses separated by commas">{{ tenant()->settings['custom_notification_emails'] ?? '' }}</textarea>
                        <p class="mt-1 text-sm text-gray-500">Separate multiple email addresses with commas</p>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle custom emails visibility based on recipients selection
document.querySelectorAll('input[name="recipients"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const customEmails = document.getElementById('customEmails');
        if (this.value === 'custom') {
            customEmails.classList.remove('hidden');
        } else {
            customEmails.classList.add('hidden');
        }
    });
});
</script>
</x-app-layout>
