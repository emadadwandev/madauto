<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Payment Methods') }}
            </h2>
            <a href="{{ route('dashboard.subscription.index', ['subdomain' => request()->route('subdomain')]) }}" class="text-sm text-gray-600 hover:text-gray-900">
                &larr; Back to Subscription
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

            <!-- Default Payment Method -->
            @if($defaultPaymentMethod)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Default Payment Method</h3>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                Default
                            </span>
                        </div>

                        <div class="flex items-center justify-between p-4 border rounded-lg">
                            <div class="flex items-center">
                                @if($defaultPaymentMethod->card)
                                    <div class="flex-shrink-0">
                                        <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ ucfirst($defaultPaymentMethod->card->brand) }} •••• {{ $defaultPaymentMethod->card->last4 }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            Expires {{ $defaultPaymentMethod->card->exp_month }}/{{ $defaultPaymentMethod->card->exp_year }}
                                        </p>
                                    </div>
                                @else
                                    <div class="ml-4">
                                        <p class="text-sm font-medium text-gray-900">{{ ucfirst($defaultPaymentMethod->type) }}</p>
                                    </div>
                                @endif
                            </div>

                            <div class="flex space-x-2">
                                <button onclick="alert('Update payment method coming soon!')" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                    Update
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- All Payment Methods -->
            @if(count($paymentMethods) > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">All Payment Methods</h3>
                            <button onclick="alert('Add new payment method coming soon!')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Payment Method
                            </button>
                        </div>

                        <div class="space-y-3">
                            @foreach($paymentMethods as $method)
                                <div class="flex items-center justify-between p-4 border rounded-lg {{ $defaultPaymentMethod && $defaultPaymentMethod->id === $method->id ? 'border-indigo-300 bg-indigo-50' : '' }}">
                                    <div class="flex items-center">
                                        @if($method->card)
                                            <div class="flex-shrink-0">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <p class="text-sm font-medium text-gray-900">
                                                    {{ ucfirst($method->card->brand) }} •••• {{ $method->card->last4 }}
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    Expires {{ $method->card->exp_month }}/{{ $method->card->exp_year }}
                                                </p>
                                            </div>
                                        @else
                                            <div class="ml-4">
                                                <p class="text-sm font-medium text-gray-900">{{ ucfirst($method->type) }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex space-x-2">
                                        @if(!$defaultPaymentMethod || $defaultPaymentMethod->id !== $method->id)
                                            <button onclick="alert('Set as default coming soon!')" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                                Set as Default
                                            </button>
                                            <button onclick="if(confirm('Are you sure you want to remove this payment method?')) { alert('Remove payment method coming soon!'); }" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                Remove
                                            </button>
                                        @else
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                                Default
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <!-- No Payment Methods -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No Payment Methods</h3>
                            <p class="mt-1 text-sm text-gray-500">Add a payment method to manage your subscription.</p>
                            <div class="mt-6">
                                <button onclick="alert('Add payment method coming soon!')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Add Payment Method
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Security Note -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong class="font-semibold">Your payment information is secure.</strong>
                            All payment data is processed securely through Stripe. We never store your complete card details on our servers.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
