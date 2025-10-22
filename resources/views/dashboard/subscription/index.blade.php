<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Subscription & Billing') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-green-50 border-l-4 border-green-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Current Plan Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Current Plan</h3>
                        @if($subscription && $subscription->onTrial())
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                Trial - {{ $subscription->trialDaysRemaining() }} days remaining
                            </span>
                        @elseif($subscription && $subscription->cancel_at_period_end)
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                Cancels {{ $subscription->current_period_end->format('M d, Y') }}
                            </span>
                        @elseif($subscription && $subscription->isActive())
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Active
                            </span>
                        @elseif($subscription && $subscription->isPastDue())
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                Payment Past Due
                            </span>
                        @else
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                No Active Plan
                            </span>
                        @endif
                    </div>

                    @if($subscription)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Plan Details -->
                            <div>
                                <h4 class="text-2xl font-bold text-gray-900">{{ $subscription->plan->name }}</h4>
                                <p class="text-3xl font-bold text-indigo-600 mt-2">
                                    ${{ number_format($subscription->plan->price, 2) }}
                                    <span class="text-sm font-normal text-gray-500">/{{ $subscription->plan->billing_interval }}</span>
                                </p>
                                <div class="mt-4 space-y-2">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $subscription->plan->hasUnlimitedOrders() ? 'Unlimited' : number_format($subscription->plan->order_limit) }} orders/month
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $subscription->plan->location_limit }} locations
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $subscription->plan->user_limit }} team members
                                    </div>
                                </div>
                            </div>

                            <!-- Billing Information -->
                            <div>
                                <h5 class="text-sm font-medium text-gray-700 mb-3">Billing Information</h5>
                                <dl class="space-y-2">
                                    <div>
                                        <dt class="text-xs text-gray-500">Current Period</dt>
                                        <dd class="text-sm font-medium text-gray-900">
                                            {{ $subscription->current_period_start->format('M d, Y') }} - {{ $subscription->current_period_end->format('M d, Y') }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs text-gray-500">Next Billing Date</dt>
                                        <dd class="text-sm font-medium text-gray-900">
                                            @if($subscription->cancel_at_period_end)
                                                <span class="text-red-600">Subscription ending</span>
                                            @else
                                                {{ $subscription->current_period_end->format('M d, Y') }}
                                            @endif
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Quick Actions -->
                            <div>
                                <h5 class="text-sm font-medium text-gray-700 mb-3">Quick Actions</h5>
                                <div class="space-y-2">
                                    @if($subscription->cancel_at_period_end)
                                        <form method="POST" action="{{ route('dashboard.subscription.resume', ['subdomain' => request()->route('subdomain')]) }}">
                                            @csrf
                                            <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                                Resume Subscription
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('dashboard.subscription.plans', ['subdomain' => request()->route('subdomain')]) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                            Change Plan
                                        </a>
                                    @endif

                                    <a href="{{ route('dashboard.subscription.billing-history', ['subdomain' => request()->route('subdomain')]) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                        Billing History
                                    </a>

                                    <a href="{{ route('dashboard.subscription.payment-methods', ['subdomain' => request()->route('subdomain')]) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                        Payment Methods
                                    </a>

                                    @if(!$subscription->cancel_at_period_end)
                                        <button onclick="if(confirm('Are you sure you want to cancel your subscription? It will remain active until the end of your billing period.')) { document.getElementById('cancel-form').submit(); }" class="w-full inline-flex justify-center items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                            Cancel Subscription
                                        </button>
                                        <form id="cancel-form" method="POST" action="{{ route('dashboard.subscription.cancel', ['subdomain' => request()->route('subdomain')]) }}" class="hidden">
                                            @csrf
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No Active Subscription</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by choosing a plan that fits your needs.</p>
                            <div class="mt-6">
                                <a href="{{ route('dashboard.subscription.plans', ['subdomain' => request()->route('subdomain')]) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                    View Plans
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if($subscription)
                <!-- Usage Statistics -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Usage This Month</h3>

                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Orders Processed</span>
                                <span class="text-sm font-medium text-gray-900">
                                    {{ number_format($usageStats['current_usage']) }}
                                    @if(!$usageStats['unlimited'])
                                        / {{ number_format($usageStats['limit']) }}
                                    @else
                                        / Unlimited
                                    @endif
                                </span>
                            </div>

                            @if(!$usageStats['unlimited'])
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300"
                                         style="width: {{ min(100, $usageStats['percentage']) }}%"
                                         x-data
                                         :class="{
                                             'bg-green-500': {{ $usageStats['percentage'] }} < 50,
                                             'bg-yellow-500': {{ $usageStats['percentage'] }} >= 50 && {{ $usageStats['percentage'] }} < 80,
                                             'bg-orange-500': {{ $usageStats['percentage'] }} >= 80 && {{ $usageStats['percentage'] }} < 100,
                                             'bg-red-500': {{ $usageStats['percentage'] }} >= 100
                                         }">
                                    </div>
                                </div>
                                <div class="flex justify-between mt-1">
                                    <span class="text-xs text-gray-500">{{ number_format($usageStats['percentage'], 1) }}% used</span>
                                    @if($usageStats['remaining'] > 0)
                                        <span class="text-xs text-gray-500">{{ number_format($usageStats['remaining']) }} remaining</span>
                                    @else
                                        <span class="text-xs text-red-600 font-medium">Limit reached</span>
                                    @endif
                                </div>

                                @if($usageStats['percentage'] >= 80 && $usageStats['percentage'] < 100)
                                    <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm text-yellow-700">
                                                    You're approaching your monthly order limit. Consider upgrading your plan to avoid service interruption.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($usageStats['percentage'] >= 100)
                                    <div class="mt-4 bg-red-50 border-l-4 border-red-400 p-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm text-red-700 font-medium">
                                                    You've reached your monthly order limit. Upgrade your plan to continue processing orders.
                                                </p>
                                                <div class="mt-2">
                                                    <a href="{{ route('dashboard.subscription.plans', ['subdomain' => request()->route('subdomain')]) }}" class="text-sm font-medium text-red-700 underline hover:text-red-600">
                                                        Upgrade Now →
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <p class="text-sm text-gray-500 mt-2">You have unlimited orders on your current plan.</p>
                            @endif
                        </div>

                        <!-- Usage History Chart -->
                        @if(count($usageHistory) > 0)
                            <div class="mt-8">
                                <h4 class="text-sm font-medium text-gray-700 mb-4">Usage History (Last 6 Months)</h4>
                                <div class="grid grid-cols-6 gap-2">
                                    @foreach(array_reverse($usageHistory) as $history)
                                        <div class="text-center">
                                            <div class="h-32 flex items-end justify-center">
                                                <div class="w-full bg-indigo-600 rounded-t" style="height: {{ min(100, ($history['order_count'] / max(1, $usageStats['limit'] ?? $history['order_count'])) * 100) }}%"
                                                     title="{{ $history['order_count'] }} orders">
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <p class="text-xs font-medium text-gray-900">{{ $history['order_count'] }}</p>
                                                <p class="text-xs text-gray-500">{{ date('M', mktime(0, 0, 0, $history['month'], 1)) }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
