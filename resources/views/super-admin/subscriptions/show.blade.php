<x-super-admin-layout>
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center">
                    <a href="{{ route('super-admin.subscriptions.index') }}" class="mr-3 text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h1 class="text-2xl font-semibold text-gray-900">Subscription Details</h1>
                </div>
                <p class="mt-1 text-sm text-gray-500">{{ $subscription->tenant ? $subscription->tenant->name : 'Tenant Deleted (ID: ' . $subscription->tenant_id . ')' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Subscription Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Subscription Information</h3>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Plan</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $subscription->subscriptionPlan->name ?? 'N/A' }}
                                @if($subscription->subscriptionPlan)
                                <span class="text-gray-500">({{ $subscription->subscriptionPlan->slug }})</span>
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Price</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($subscription->subscriptionPlan)
                                {{ formatCurrency($subscription->subscriptionPlan->price, config('currencies.default')) }} / {{ $subscription->subscriptionPlan->billing_period }}
                                @else
                                N/A
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                @if($subscription->status === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @elseif($subscription->status === 'trialing')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Trialing
                                    </span>
                                @elseif($subscription->status === 'cancelled')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        Cancelled
                                    </span>
                                @elseif($subscription->status === 'past_due')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Past Due
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ ucfirst($subscription->status) }}
                                    </span>
                                @endif
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Started</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $subscription->created_at->format('M d, Y') }}</dd>
                        </div>

                        @if($subscription->ends_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Ends At</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $subscription->ends_at->format('M d, Y') }}
                                @if($subscription->ends_at->isFuture())
                                    <span class="text-xs text-gray-500">({{ $subscription->ends_at->diffForHumans() }})</span>
                                @endif
                            </dd>
                        </div>
                        @endif

                        @if($subscription->cancel_at_period_end)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Cancellation</dt>
                            <dd class="mt-1 text-sm text-red-600">
                                Will cancel at period end
                            </dd>
                        </div>
                        @endif

                        @if($subscription->stripe_id)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Stripe ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono text-xs">{{ $subscription->stripe_id }}</dd>
                        </div>
                        @endif
                    </dl>

                    <!-- Actions -->
                    <div class="mt-6 flex flex-wrap gap-3">
                        @if($subscription->status === 'active' && !$subscription->cancel_at_period_end)
                        <form method="POST" action="{{ route('super-admin.subscriptions.cancel', $subscription) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('Are you sure you want to cancel this subscription?')"
                                    class="inline-flex items-center px-3 py-2 border border-orange-300 rounded-md text-sm font-medium text-orange-700 bg-white hover:bg-orange-50">
                                Cancel Subscription
                            </button>
                        </form>
                        @endif

                        @if($subscription->status === 'cancelled')
                        <form method="POST" action="{{ route('super-admin.subscriptions.resume', $subscription) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-2 border border-green-300 rounded-md text-sm font-medium text-green-700 bg-white hover:bg-green-50">
                                Resume Subscription
                            </button>
                        </form>
                        @endif

                        <button type="button"
                                x-data
                                @click="$refs.changePlanModal.showModal()"
                                class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-md text-sm font-medium text-blue-700 bg-white hover:bg-blue-50">
                            Change Plan
                        </button>

                        <button type="button"
                                x-data
                                @click="$refs.extendTrialModal.showModal()"
                                class="inline-flex items-center px-3 py-2 border border-indigo-300 rounded-md text-sm font-medium text-indigo-700 bg-white hover:bg-indigo-50">
                            Extend Trial
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tenant Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Tenant Information</h3>
                    @if($subscription->tenant)
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $subscription->tenant->name }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $subscription->tenant->email }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Subdomain</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $subscription->tenant->subdomain }}</dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Tenant Status</dt>
                                <dd class="mt-1">
                                    @if($subscription->tenant->status === 'active')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @elseif($subscription->tenant->status === 'suspended')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Suspended
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            {{ ucfirst($subscription->tenant->status) }}
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-6">
                            <a href="{{ route('super-admin.tenants.show', $subscription->tenant) }}"
                               class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                View Tenant Details
                            </a>
                        </div>
                    @else
                        <div class="rounded-md bg-red-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">
                                        Tenant Not Found
                                    </h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>The tenant associated with this subscription has been deleted.</p>
                                        <p class="mt-1">Tenant ID: <span class="font-mono">{{ $subscription->tenant_id }}</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Usage History -->
        @if($usageHistory->isNotEmpty())
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Usage History (Last 6 Months)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Period
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Orders
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Users
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Last Updated
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($usageHistory as $usage)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ \Carbon\Carbon::create($usage->year, $usage->month, 1)->format('F Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $usage->orders_count ?? 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $usage->users_count ?? 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $usage->updated_at->format('M d, Y') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Change Plan Modal -->
    <dialog x-ref="changePlanModal" class="rounded-lg shadow-xl backdrop:bg-gray-900/50">
        <div class="bg-white p-6 rounded-lg w-96">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Change Subscription Plan</h3>
            <form method="POST" action="{{ route('super-admin.subscriptions.change-plan', $subscription) }}">
                @csrf
                <div class="mb-4">
                    <label for="plan_id" class="block text-sm font-medium text-gray-700 mb-2">Select New Plan</label>
                    <select name="plan_id" id="plan_id" required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">Choose a plan...</option>
                        <!-- TODO: Dynamically load plans from database -->
                        <option value="1">Basic - $29/month</option>
                        <option value="2">Professional - $79/month</option>
                        <option value="3">Enterprise - $199/month</option>
                    </select>
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button"
                            @click="$refs.changePlanModal.close()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Change Plan
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Extend Trial Modal -->
    <dialog x-ref="extendTrialModal" class="rounded-lg shadow-xl backdrop:bg-gray-900/50">
        <div class="bg-white p-6 rounded-lg w-96">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Extend Trial Period</h3>
            <form method="POST" action="{{ route('super-admin.subscriptions.extend-trial', $subscription) }}">
                @csrf
                <div class="mb-4">
                    <label for="days" class="block text-sm font-medium text-gray-700 mb-2">Additional Days</label>
                    <input type="number" name="days" id="days" min="1" max="90" value="7" required
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <p class="mt-1 text-sm text-gray-500">Max 90 days</p>
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button"
                            @click="$refs.extendTrialModal.close()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        Extend Trial
                    </button>
                </div>
            </form>
        </div>
    </dialog>
</x-super-admin-layout>
