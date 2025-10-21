<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-extrabold text-gray-900 mb-4">Welcome! Let's Get You Set Up</h1>
                <p class="text-xl text-gray-600">Follow these simple steps to start syncing your orders</p>
            </div>

            <!-- Progress Indicator -->
            <div class="mb-12" x-data="{ currentStep: {{ $onboardingStatus['loyverse_connected'] ? ($onboardingStatus['careem_configured'] ? 3 : 2) : 1 }} }">
                <div class="flex items-center justify-between">
                    <!-- Step 1 -->
                    <div class="flex-1">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div :class="currentStep >= 1 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600'" class="w-10 h-10 rounded-full flex items-center justify-center font-bold">
                                    <span x-show="!{{ $onboardingStatus['loyverse_connected'] ? 'true' : 'false' }}">1</span>
                                    <svg x-show="{{ $onboardingStatus['loyverse_connected'] ? 'true' : 'false' }}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium" :class="currentStep >= 1 ? 'text-indigo-600' : 'text-gray-500'">Connect Loyverse</p>
                            </div>
                        </div>
                    </div>

                    <!-- Connector Line -->
                    <div class="flex-1 px-2">
                        <div :class="currentStep >= 2 ? 'bg-indigo-600' : 'bg-gray-300'" class="h-1 w-full"></div>
                    </div>

                    <!-- Step 2 -->
                    <div class="flex-1">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div :class="currentStep >= 2 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600'" class="w-10 h-10 rounded-full flex items-center justify-center font-bold">
                                    <span x-show="!{{ $onboardingStatus['careem_configured'] ? 'true' : 'false' }}">2</span>
                                    <svg x-show="{{ $onboardingStatus['careem_configured'] ? 'true' : 'false' }}" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium" :class="currentStep >= 2 ? 'text-indigo-600' : 'text-gray-500'">Configure Careem</p>
                            </div>
                        </div>
                    </div>

                    <!-- Connector Line -->
                    <div class="flex-1 px-2">
                        <div :class="currentStep >= 3 ? 'bg-indigo-600' : 'bg-gray-300'" class="h-1 w-full"></div>
                    </div>

                    <!-- Step 3 -->
                    <div class="flex-1">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div :class="currentStep >= 3 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-600'" class="w-10 h-10 rounded-full flex items-center justify-center font-bold">
                                    <span>3</span>
                                </div>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium" :class="currentStep >= 3 ? 'text-indigo-600' : 'text-gray-500'">Complete</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 1: Connect Loyverse -->
            @if(!$onboardingStatus['loyverse_connected'])
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Step 1: Connect Your Loyverse Account</h2>
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
                    <form action="{{ route('dashboard.onboarding.loyverse.save') }}" method="POST">
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
                            <a href="{{ route('dashboard.onboarding.skip') }}" class="text-sm text-gray-600 hover:text-gray-900">Skip for now</a>
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-colors shadow-lg">
                                Connect Loyverse
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
                            <h3 class="text-lg font-semibold text-green-800">Loyverse Connected!</h3>
                            <p class="text-sm text-green-700">Your Loyverse account is successfully connected and ready to receive orders.</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 2: Configure Careem Webhook -->
            @if($onboardingStatus['loyverse_connected'] && !$onboardingStatus['careem_configured'])
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Step 2: Configure Careem Webhook</h2>
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
                    <form action="{{ route('dashboard.onboarding.webhook.generate') }}" method="POST">
                        @csrf

                        <div class="flex items-center justify-between">
                            <a href="{{ route('dashboard.onboarding.skip') }}" class="text-sm text-gray-600 hover:text-gray-900">Skip for now</a>
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors shadow-lg">
                                Generate Webhook Secret
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            @elseif($onboardingStatus['careem_configured'])
                <!-- Step 2 Complete -->
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

                                @if($careemCredential)
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Webhook Secret:</label>
                                        <div class="flex items-center">
                                            <code class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded text-sm">
                                                {{ $careemCredential->credentials['webhook_secret'] ?? 'N/A' }}
                                            </code>
                                            <button onclick="copyToClipboard('{{ $careemCredential->credentials['webhook_secret'] ?? '' }}')" class="ml-2 px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded text-sm">
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

            <!-- Step 3: Complete Onboarding -->
            @if($onboardingStatus['loyverse_connected'] && $onboardingStatus['careem_configured'])
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full mb-4">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>

                        <h2 class="text-3xl font-extrabold text-gray-900 mb-4">You're All Set!</h2>
                        <p class="text-lg text-gray-600 mb-8">Your integration is configured and ready to sync orders automatically.</p>

                        <form action="{{ route('dashboard.onboarding.complete') }}" method="POST" class="inline">
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
