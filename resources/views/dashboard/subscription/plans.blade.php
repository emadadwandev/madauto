<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Choose Your Plan') }}
            </h2>
            @if($currentSubscription)
                <a href="{{ route('dashboard.subscription.index', ['subdomain' => request()->route('subdomain')]) }}" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Back to Subscription
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">Simple, Transparent Pricing</h2>
                <p class="mt-4 text-lg text-gray-600">Choose the perfect plan for your business</p>
                <p class="mt-2 text-sm text-gray-500">All plans include a 14-day free trial. No credit card required to start.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($plans as $plan)
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden
                                {{ $currentSubscription && $currentSubscription->subscription_plan_id == $plan->id ? 'ring-2 ring-indigo-600' : '' }}">
                        <!-- Plan Header -->
                        <div class="px-6 py-8 {{ $plan->slug === 'business' ? 'bg-indigo-600' : 'bg-gray-50' }}">
                            @if($currentSubscription && $currentSubscription->subscription_plan_id == $plan->id)
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 mb-4">
                                    Current Plan
                                </span>
                            @endif
                            @if($plan->slug === 'business')
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 mb-4">
                                    Most Popular
                                </span>
                            @endif

                            <h3 class="text-2xl font-bold {{ $plan->slug === 'business' ? 'text-white' : 'text-gray-900' }}">
                                {{ $plan->name }}
                            </h3>
                            <div class="mt-4">
                                <span class="text-4xl font-bold {{ $plan->slug === 'business' ? 'text-white' : 'text-gray-900' }}">
                                    ${{ number_format($plan->price, 0) }}
                                </span>
                                <span class="text-lg {{ $plan->slug === 'business' ? 'text-indigo-100' : 'text-gray-500' }}">
                                    /{{ $plan->billing_interval }}
                                </span>
                            </div>
                        </div>

                        <!-- Plan Features -->
                        <div class="px-6 py-8">
                            <ul class="space-y-4">
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-700">
                                        <strong class="font-semibold text-gray-900">
                                            {{ $plan->hasUnlimitedOrders() ? 'Unlimited' : number_format($plan->order_limit) }}
                                        </strong> orders per month
                                    </span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-700">
                                        <strong class="font-semibold text-gray-900">{{ $plan->location_limit }}</strong>
                                        {{ $plan->location_limit == 1 ? 'location' : 'locations' }}
                                    </span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-700">
                                        <strong class="font-semibold text-gray-900">{{ $plan->user_limit }}</strong>
                                        team {{ $plan->user_limit == 1 ? 'member' : 'members' }}
                                    </span>
                                </li>
                                @foreach($plan->getFeatures() as $feature)
                                    <li class="flex items-start">
                                        <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-gray-700">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <!-- CTA Button -->
                            <div class="mt-8">
                                @if($currentSubscription)
                                    @if($currentSubscription->subscription_plan_id == $plan->id)
                                        <button disabled class="w-full py-3 px-4 rounded-md font-semibold text-sm bg-gray-100 text-gray-400 cursor-not-allowed">
                                            Current Plan
                                        </button>
                                    @else
                                        <form method="POST" action="{{ route('dashboard.subscription.change-plan', ['subdomain' => request()->route('subdomain')]) }}">
                                            @csrf
                                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                            <button type="submit"
                                                    onclick="return confirm('Are you sure you want to change to the {{ $plan->name }} plan?')"
                                                    class="w-full py-3 px-4 rounded-md font-semibold text-sm
                                                    {{ $plan->slug === 'business' ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-gray-900 hover:bg-gray-800 text-white' }}
                                                    transition duration-150">
                                                @if($plan->price > $currentSubscription->plan->price)
                                                    Upgrade to {{ $plan->name }}
                                                @else
                                                    Downgrade to {{ $plan->name }}
                                                @endif
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <form method="POST" action="{{ route('dashboard.subscription.subscribe', ['subdomain' => request()->route('subdomain')]) }}">
                                        @csrf
                                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                        <button type="submit"
                                                class="w-full py-3 px-4 rounded-md font-semibold text-sm
                                                {{ $plan->slug === 'business' ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-gray-900 hover:bg-gray-800 text-white' }}
                                                transition duration-150">
                                            Start Free Trial
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- FAQ Section -->
            <div class="mt-16 bg-white rounded-lg shadow-sm p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Frequently Asked Questions</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">Can I change plans later?</h4>
                        <p class="text-gray-600 text-sm">Yes! You can upgrade or downgrade your plan at any time. Upgrades take effect immediately, while downgrades take effect at the end of your billing period.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">What happens if I exceed my order limit?</h4>
                        <p class="text-gray-600 text-sm">You'll receive notifications when approaching your limit. Once reached, you'll need to upgrade to continue processing orders for that month.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">How does the free trial work?</h4>
                        <p class="text-gray-600 text-sm">All plans include a 14-day free trial. You won't be charged until the trial period ends. Cancel anytime during the trial at no cost.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">Can I cancel my subscription?</h4>
                        <p class="text-gray-600 text-sm">Yes, you can cancel anytime. Your service will continue until the end of your current billing period, with no additional charges.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
