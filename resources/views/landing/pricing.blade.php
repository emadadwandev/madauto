@extends('landing.layout')

@section('title', 'Pricing Plans')
@section('description', 'Choose the perfect plan for your restaurant. Start with a 14-day free trial, no credit card required.')

@section('content')

<!-- Pricing Hero -->
<section class="pt-20 pb-12 bg-gradient-to-br from-indigo-50 via-white to-purple-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div data-aos="fade-up">
            <h1 class="text-5xl md:text-6xl font-extrabold text-gray-900 mb-6">
                Simple, Transparent Pricing
            </h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto mb-8">
                Choose the plan that fits your restaurant. All plans include a 14-day free trial.
            </p>
            <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                No credit card required for trial
            </div>
        </div>
    </div>
</section>

<!-- Pricing Cards -->
<section class="pb-20 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-3 gap-8 -mt-8">
            @foreach($plans as $index => $plan)
                <!-- Plan Card -->
                <div class="relative bg-white rounded-2xl shadow-xl {{ $plan->slug === 'business' ? 'border-4 border-indigo-600 transform md:scale-105' : 'border border-gray-200' }}" data-aos="fade-up" data-aos-delay="{{ ($index + 1) * 100 }}">
                    <!-- Most Popular Badge -->
                    @if($plan->slug === 'business')
                        <div class="absolute -top-5 left-1/2 transform -translate-x-1/2">
                            <span class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-1 rounded-full text-sm font-bold shadow-lg">
                                Most Popular
                            </span>
                        </div>
                    @endif

                    <div class="p-8">
                        <!-- Plan Name -->
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h3>

                        <!-- Price -->
                        <div class="mb-6">
                            <span class="text-5xl font-extrabold text-gray-900">${{ number_format($plan->price, 0) }}</span>
                            <span class="text-gray-600">/month</span>
                        </div>

                        <!-- Description -->
                        <p class="text-gray-600 mb-6">
                            @if($plan->slug === 'starter')
                                Perfect for small restaurants starting their automation journey
                            @elseif($plan->slug === 'business')
                                Ideal for growing restaurants with multiple locations
                            @else
                                For large restaurant chains with unlimited needs
                            @endif
                        </p>

                        <!-- CTA Button -->
                        <a href="{{ route('landing.register', ['plan' => $plan->slug]) }}" class="block w-full text-center px-6 py-3 rounded-lg font-semibold transition-all mb-8 {{ $plan->slug === 'business' ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white hover:from-indigo-700 hover:to-purple-700 shadow-lg hover:shadow-xl' : 'bg-gray-100 text-gray-900 hover:bg-gray-200' }}">
                            Start Free Trial
                        </a>

                        <!-- Features List -->
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">
                                    <strong>{{ $plan->order_limit ? number_format($plan->order_limit) : 'Unlimited' }}</strong> orders/month
                                </span>
                            </div>

                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">
                                    <strong>{{ $plan->location_limit === 0 ? 'Unlimited' : $plan->location_limit }}</strong> {{ $plan->location_limit === 1 ? 'location' : 'locations' }}
                                </span>
                            </div>

                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700">
                                    <strong>{{ $plan->user_limit === 0 ? 'Unlimited' : $plan->user_limit }}</strong> team {{ $plan->user_limit === 1 ? 'member' : 'members' }}
                                </span>
                            </div>

                            @if($plan->features)
                                @foreach($plan->features as $feature)
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-gray-700">{{ $feature }}</span>
                                    </div>
                                @endforeach
                            @endif

                            <!-- Common Features -->
                            <div class="pt-4 mt-4 border-t border-gray-200">
                                <p class="text-sm font-semibold text-gray-900 mb-3">All plans include:</p>

                                <div class="flex items-start mb-2">
                                    <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm text-gray-600">Real-time order sync</span>
                                </div>

                                <div class="flex items-start mb-2">
                                    <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm text-gray-600">Product mapping</span>
                                </div>

                                <div class="flex items-start mb-2">
                                    <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm text-gray-600">Automatic retry logic</span>
                                </div>

                                <div class="flex items-start mb-2">
                                    <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm text-gray-600">Detailed analytics</span>
                                </div>

                                <div class="flex items-start mb-2">
                                    <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm text-gray-600">Email support</span>
                                </div>

                                @if($plan->slug !== 'starter')
                                    <div class="flex items-start mb-2">
                                        <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm text-gray-600">Priority support</span>
                                    </div>
                                @endif

                                @if($plan->slug === 'enterprise')
                                    <div class="flex items-start mb-2">
                                        <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm text-gray-600">Dedicated account manager</span>
                                    </div>

                                    <div class="flex items-start">
                                        <svg class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm text-gray-600">Custom integrations</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Feature Comparison Table -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl font-extrabold text-gray-900 mb-4">Detailed Feature Comparison</h2>
            <p class="text-lg text-gray-600">Compare all features across our plans</p>
        </div>

        <div class="overflow-x-auto" data-aos="fade-up">
            <table class="w-full bg-white rounded-lg overflow-hidden shadow-lg">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900">Feature</th>
                        @foreach($plans as $plan)
                            <th class="px-6 py-4 text-center text-sm font-semibold {{ $plan->slug === 'business' ? 'text-indigo-600' : 'text-gray-900' }}">
                                {{ $plan->name }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">Orders per Month</td>
                        @foreach($plans as $plan)
                            <td class="px-6 py-4 text-sm text-center {{ $plan->slug === 'business' ? 'bg-indigo-50' : '' }}">
                                {{ $plan->order_limit ? number_format($plan->order_limit) : 'Unlimited' }}
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">Locations</td>
                        @foreach($plans as $plan)
                            <td class="px-6 py-4 text-sm text-center {{ $plan->slug === 'business' ? 'bg-indigo-50' : '' }}">
                                {{ $plan->location_limit === 0 ? 'Unlimited' : $plan->location_limit }}
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">Team Members</td>
                        @foreach($plans as $plan)
                            <td class="px-6 py-4 text-sm text-center {{ $plan->slug === 'business' ? 'bg-indigo-50' : '' }}">
                                {{ $plan->user_limit === 0 ? 'Unlimited' : $plan->user_limit }}
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">Real-Time Sync</td>
                        @foreach($plans as $plan)
                            <td class="px-6 py-4 text-center {{ $plan->slug === 'business' ? 'bg-indigo-50' : '' }}">
                                <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">Product Mapping</td>
                        @foreach($plans as $plan)
                            <td class="px-6 py-4 text-center {{ $plan->slug === 'business' ? 'bg-indigo-50' : '' }}">
                                <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">Analytics & Reports</td>
                        @foreach($plans as $plan)
                            <td class="px-6 py-4 text-center {{ $plan->slug === 'business' ? 'bg-indigo-50' : '' }}">
                                <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">Email Support</td>
                        @foreach($plans as $plan)
                            <td class="px-6 py-4 text-center {{ $plan->slug === 'business' ? 'bg-indigo-50' : '' }}">
                                <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">Priority Support</td>
                        <td class="px-6 py-4 text-center">
                            <svg class="w-5 h-5 text-gray-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </td>
                        <td class="px-6 py-4 text-center bg-indigo-50">
                            <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">Dedicated Account Manager</td>
                        <td class="px-6 py-4 text-center">
                            <svg class="w-5 h-5 text-gray-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </td>
                        <td class="px-6 py-4 text-center bg-indigo-50">
                            <svg class="w-5 h-5 text-gray-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">Custom Integrations</td>
                        <td class="px-6 py-4 text-center">
                            <svg class="w-5 h-5 text-gray-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </td>
                        <td class="px-6 py-4 text-center bg-indigo-50">
                            <svg class="w-5 h-5 text-gray-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <svg class="w-5 h-5 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Pricing FAQ -->
<section class="py-20 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl font-extrabold text-gray-900 mb-4">Pricing FAQs</h2>
        </div>

        <div class="space-y-4" x-data="{ openFaq: null }">
            <!-- FAQ 1 -->
            <div class="border border-gray-200 rounded-lg overflow-hidden" data-aos="fade-up">
                <button @click="openFaq = openFaq === 1 ? null : 1" class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 focus:outline-none flex items-center justify-between">
                    <span class="font-semibold text-gray-900">Can I change plans later?</span>
                    <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'transform rotate-180': openFaq === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 1" x-cloak class="px-6 py-4 bg-gray-50">
                    <p class="text-gray-700">Absolutely! You can upgrade or downgrade your plan at any time. Upgrades are effective immediately, while downgrades take effect at the end of your current billing period.</p>
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="border border-gray-200 rounded-lg overflow-hidden" data-aos="fade-up" data-aos-delay="100">
                <button @click="openFaq = openFaq === 2 ? null : 2" class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 focus:outline-none flex items-center justify-between">
                    <span class="font-semibold text-gray-900">What happens if I exceed my limit?</span>
                    <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'transform rotate-180': openFaq === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 2" x-cloak class="px-6 py-4 bg-gray-50">
                    <p class="text-gray-700">We'll notify you when you reach 80% of your order limit. If you exceed it, we'll suggest upgrading to a higher plan. Your service continues without interruption - we just want to ensure you have the right plan for your needs.</p>
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="border border-gray-200 rounded-lg overflow-hidden" data-aos="fade-up" data-aos-delay="200">
                <button @click="openFaq = openFaq === 3 ? null : 3" class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 focus:outline-none flex items-center justify-between">
                    <span class="font-semibold text-gray-900">Do you offer annual billing?</span>
                    <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'transform rotate-180': openFaq === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 3" x-cloak class="px-6 py-4 bg-gray-50">
                    <p class="text-gray-700">Yes! Contact us to discuss annual billing options, which come with a discount. Annual plans are billed once per year and offer significant savings compared to monthly billing.</p>
                </div>
            </div>

            <!-- FAQ 4 -->
            <div class="border border-gray-200 rounded-lg overflow-hidden" data-aos="fade-up" data-aos-delay="300">
                <button @click="openFaq = openFaq === 4 ? null : 4" class="w-full px-6 py-4 text-left bg-white hover:bg-gray-50 focus:outline-none flex items-center justify-between">
                    <span class="font-semibold text-gray-900">Can I get a custom Enterprise plan?</span>
                    <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{ 'transform rotate-180': openFaq === 4 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openFaq === 4" x-cloak class="px-6 py-4 bg-gray-50">
                    <p class="text-gray-700">Definitely! For large restaurant chains or unique requirements, we can create a custom plan tailored to your needs. Contact our sales team to discuss your specific requirements.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA -->
<section class="py-20 bg-gradient-to-br from-indigo-600 via-purple-600 to-indigo-700 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center" data-aos="zoom-in">
        <h2 class="text-4xl font-extrabold mb-4">Ready to Get Started?</h2>
        <p class="text-xl mb-8 opacity-90">Start your 14-day free trial today. No credit card required.</p>

        <a href="{{ route('landing.register') }}" class="inline-flex items-center justify-center px-8 py-4 bg-white text-indigo-600 font-bold rounded-lg hover:bg-gray-100 transition-all shadow-xl hover:shadow-2xl transform hover:-translate-y-0.5">
            Start Free Trial
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </a>
    </div>
</section>

@endsection

@push('styles')
<style>
    [x-cloak] {
        display: none !important;
    }
</style>
@endpush
