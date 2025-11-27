<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('API Credentials & Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Loyverse Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Loyverse API Settings</h3>

                    <form method="POST" action="{{ route('api-credentials.store', ['subdomain' => request()->route('subdomain')]) }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="service" value="loyverse">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="loyverse_access_token" class="block text-gray-700 text-sm font-bold mb-2">
                                    Access Token *
                                </label>
                                <input type="password" name="credential_value" id="loyverse_access_token" placeholder="Enter Loyverse Access Token" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <input type="hidden" name="credential_type" value="access_token">
                            </div>

                            <div class="flex items-end">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Save Access Token
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Test Connection -->
                    <div class="mt-6 pt-6 border-t">
                        <form method="POST" action="{{ route('api-credentials.test-connection', ['subdomain' => request()->route('subdomain')]) }}">
                            @csrf
                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Test Loyverse Connection
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Careem Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Careem Webhook Settings</h3>

                    <form method="POST" action="{{ route('api-credentials.store', ['subdomain' => request()->route('subdomain')]) }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="service" value="careem">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="careem_webhook_secret" class="block text-gray-700 text-sm font-bold mb-2">
                                    Webhook Secret *
                                </label>
                                <input type="password" name="credential_value" id="careem_webhook_secret" placeholder="Enter Careem Webhook Secret" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <input type="hidden" name="credential_type" value="webhook_secret">
                                <p class="text-gray-600 text-xs italic mt-1">Used to verify incoming webhook requests from Careem</p>
                            </div>

                            <div class="flex items-end">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Save Webhook Secret
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Webhook URL -->
                    <div class="mt-6 pt-6 border-t">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Webhook URL (Provide this to Careem)
                        </label>
                        <div class="flex items-center bg-gray-100 p-3 rounded">
                            <code class="flex-1 text-sm">{{ url('/api/webhook/careem/' . request()->route('subdomain')) }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ url('/api/webhook/careem/' . request()->route('subdomain')) }}')" class="ml-2 bg-gray-500 hover:bg-gray-700 text-white font-bold py-1 px-3 rounded text-sm">
                                Copy
                            </button>
                        </div>
                        <p class="text-gray-600 text-xs italic mt-2">
                            <strong>Important:</strong> This URL is specific to your tenant. Each tenant has a unique webhook URL.
                        </p>

                        <label class="block text-gray-700 text-sm font-bold mb-2 mt-4">
                            x-careem-api-key (Provide this to Careem)
                        </label>
                        <div class="flex items-center bg-gray-100 p-3 rounded">
                            <code class="flex-1 text-sm">{{ tenant()->careem_api_key }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ tenant()->careem_api_key }}')" class="ml-2 bg-gray-500 hover:bg-gray-700 text-white font-bold py-1 px-3 rounded text-sm">
                                Copy
                            </button>
                        </div>
                        <p class="text-gray-600 text-xs italic mt-2">
                            <strong>Important:</strong> This key is required for Careem to authenticate with your webhook.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Talabat Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Talabat Webhook Settings</h3>

                    <form method="POST" action="{{ route('api-credentials.store', ['subdomain' => request()->route('subdomain')]) }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="service" value="talabat">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="talabat_api_key" class="block text-gray-700 text-sm font-bold mb-2">
                                    API Key *
                                </label>
                                <input type="password" name="credential_value" id="talabat_api_key" placeholder="Enter Talabat API Key" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <input type="hidden" name="credential_type" value="api_key">
                                <p class="text-gray-600 text-xs italic mt-1">Used to authenticate incoming webhook requests from Talabat</p>
                            </div>

                            <div class="flex items-end">
                                <button type="submit" class="bg-orange-500 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded">
                                    Save API Key
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Webhook URL -->
                    <div class="mt-6 pt-6 border-t">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Webhook URL (Provide this to Talabat)
                        </label>
                        <div class="flex items-center bg-gray-100 p-3 rounded">
                            <code class="flex-1 text-sm">{{ url('/api/webhook/talabat/' . request()->route('subdomain')) }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ url('/api/webhook/talabat/' . request()->route('subdomain')) }}')" class="ml-2 bg-gray-500 hover:bg-gray-700 text-white font-bold py-1 px-3 rounded text-sm">
                                Copy
                            </button>
                        </div>
                        <p class="text-gray-600 text-xs italic mt-2">
                            Send the API Key as a Bearer token: <code class="bg-gray-100 px-1 py-0.5 rounded">Authorization: Bearer YOUR_API_KEY</code><br>
                            Or as a custom header: <code class="bg-gray-100 px-1 py-0.5 rounded">X-Talabat-API-Key: YOUR_API_KEY</code><br>
                            <strong>Important:</strong> This URL is specific to your tenant. Each tenant has a unique webhook URL.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="my-8 border-t-4 border-indigo-500"></div>
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Platform Catalog API Settings</h2>
                <p class="text-gray-600 mt-2">Configure menu publishing to Careem and Talabat</p>
            </div>

            <!-- Careem Catalog API -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Careem Catalog API (Menu Publishing)</h3>
                        @if($credentials->has('careem_catalog'))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                ✓ Configured
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Not Configured
                            </span>
                        @endif
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>What is this?</strong> These credentials allow the system to automatically publish your menus to Careem when you click "Publish" in the menu management area.
                                    <br><strong>Get credentials:</strong> Contact your Careem account manager or visit the Careem Partner Portal to request Catalog API access.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('api-credentials.careem-catalog.store', ['subdomain' => request()->route('subdomain')]) }}" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="careem_client_id" class="block text-gray-700 text-sm font-bold mb-2">
                                    Client ID *
                                </label>
                                <input
                                    type="text"
                                    name="client_id"
                                    id="careem_client_id"
                                    value="{{ $credentials->get('careem_catalog')?->credentials['client_id'] ?? '' }}"
                                    placeholder="Enter Careem Client ID"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    required>
                            </div>

                            <div>
                                <label for="careem_client_secret" class="block text-gray-700 text-sm font-bold mb-2">
                                    Client Secret *
                                </label>
                                <input
                                    type="password"
                                    name="client_secret"
                                    id="careem_client_secret"
                                    placeholder="Enter Careem Client Secret"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    required>
                                <p class="text-gray-600 text-xs italic mt-1">For security, secret is not displayed after saving</p>
                            </div>

                            <div>
                                <label for="careem_restaurant_id" class="block text-gray-700 text-sm font-bold mb-2">
                                    Restaurant ID (Optional)
                                </label>
                                <input
                                    type="text"
                                    name="restaurant_id"
                                    id="careem_restaurant_id"
                                    value="{{ $credentials->get('careem_catalog')?->credentials['restaurant_id'] ?? '' }}"
                                    placeholder="Your Careem Restaurant ID"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>

                            <div>
                                <label for="careem_api_url" class="block text-gray-700 text-sm font-bold mb-2">
                                    API URL (Optional - Leave blank for default)
                                </label>
                                <input
                                    type="url"
                                    name="api_url"
                                    id="careem_api_url"
                                    value="{{ $credentials->get('careem_catalog')?->credentials['api_url'] ?? '' }}"
                                    placeholder="https://api-staging.careemnow.com"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                                💾 Save Careem Credentials
                            </button>
                        </div>
                    </form>

                    <!-- Test Connection -->
                    @if($credentials->has('careem_catalog'))
                        <div class="mt-6 pt-6 border-t">
                            <form method="POST" action="{{ route('api-credentials.careem-catalog.test', ['subdomain' => request()->route('subdomain')]) }}">
                                @csrf
                                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    🔌 Test Careem Connection
                                </button>
                                <p class="text-gray-600 text-xs italic mt-2">Tests OAuth authentication with Careem's API</p>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Talabat Catalog API -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Talabat Catalog API (Menu Publishing)</h3>
                        @if($credentials->has('talabat'))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                ✓ Configured
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Not Configured
                            </span>
                        @endif
                    </div>

                    <div class="bg-orange-50 border-l-4 border-orange-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-orange-700">
                                    <strong>What is this?</strong> These credentials allow the system to automatically publish your menus to Talabat (Delivery Hero) when you click "Publish" in the menu management area.
                                    <br><strong>Get credentials:</strong> Contact your Talabat account manager or visit the Delivery Hero Partner Portal to request POS Middleware API access.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('api-credentials.talabat-catalog.store', ['subdomain' => request()->route('subdomain')]) }}" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="talabat_client_id" class="block text-gray-700 text-sm font-bold mb-2">
                                    Client ID *
                                </label>
                                <input
                                    type="text"
                                    name="client_id"
                                    id="talabat_client_id"
                                    value="{{ $credentials->get('talabat')?->credentials['client_id'] ?? '' }}"
                                    placeholder="Enter Talabat Client ID"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    required>
                            </div>

                            <div>
                                <label for="talabat_client_secret" class="block text-gray-700 text-sm font-bold mb-2">
                                    Client Secret *
                                </label>
                                <input
                                    type="password"
                                    name="client_secret"
                                    id="talabat_client_secret"
                                    placeholder="Enter Talabat Client Secret"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    required>
                                <p class="text-gray-600 text-xs italic mt-1">For security, secret is not displayed after saving</p>
                            </div>

                            <div>
                                <label for="talabat_chain_code" class="block text-gray-700 text-sm font-bold mb-2">
                                    Chain Code *
                                </label>
                                <input
                                    type="text"
                                    name="chain_code"
                                    id="talabat_chain_code"
                                    value="{{ $credentials->get('talabat')?->credentials['chain_code'] ?? '' }}"
                                    placeholder="Your Talabat Chain Code"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    required>
                                <p class="text-gray-600 text-xs italic mt-1">Restaurant chain identifier (e.g., "my_restaurant_chain")</p>
                            </div>

                            <div>
                                <label for="talabat_vendor_id" class="block text-gray-700 text-sm font-bold mb-2">
                                    Vendor ID (Optional)
                                </label>
                                <input
                                    type="text"
                                    name="vendor_id"
                                    id="talabat_vendor_id"
                                    value="{{ $credentials->get('talabat')?->credentials['vendor_id'] ?? '' }}"
                                    placeholder="Your Talabat Vendor ID"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>

                            <div class="md:col-span-2">
                                <label for="talabat_api_url" class="block text-gray-700 text-sm font-bold mb-2">
                                    API URL (Optional - Leave blank for default)
                                </label>
                                <input
                                    type="url"
                                    name="api_url"
                                    id="talabat_api_url"
                                    value="{{ $credentials->get('talabat')?->credentials['api_url'] ?? '' }}"
                                    placeholder="https://integration-middleware.stg.restaurant-partners.com"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit" class="bg-orange-500 hover:bg-orange-700 text-white font-bold py-2 px-6 rounded">
                                💾 Save Talabat Credentials
                            </button>
                        </div>
                    </form>

                    <!-- Test Connection -->
                    @if($credentials->has('talabat'))
                        <div class="mt-6 pt-6 border-t">
                            <form method="POST" action="{{ route('api-credentials.talabat-catalog.test', ['subdomain' => request()->route('subdomain')]) }}">
                                @csrf
                                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    🔌 Test Talabat Connection
                                </button>
                                <p class="text-gray-600 text-xs italic mt-2">Tests OAuth authentication with Talabat's Delivery Hero API</p>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Saved Credentials -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Saved Credentials</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($credentials as $credential)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ ucfirst($credential->service) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ str_replace('_', ' ', ucfirst($credential->credential_type)) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <form method="POST" action="{{ route('api-credentials.toggle', ['apiCredential' => $credential, 'subdomain' => request()->route('subdomain')]) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $credential->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $credential->is_active ? 'Active' : 'Inactive' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $credential->updated_at->format('Y-m-d H:i:s') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <form method="POST" action="{{ route('api-credentials.destroy', ['apiCredential' => $credential, 'subdomain' => request()->route('subdomain')]) }}" class="inline" onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            No credentials saved yet. Add your first credential above.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Configuration Info -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Note:</strong> All credentials are encrypted before being stored in the database. Make sure to test the connection after saving Loyverse credentials.
                        </p>
                        <p class="text-sm text-blue-700 mt-2">
                            <strong>Platform Setup:</strong>
                        </p>
                        <ul class="text-sm text-blue-700 ml-4 mt-1 list-disc">
                            <li><strong>Loyverse:</strong> Get your Access Token from Loyverse Back Office > Settings > API Access</li>
                            <li><strong>Careem:</strong> Webhook Secret provided by Careem for HMAC signature verification</li>
                            <li><strong>Talabat:</strong> API Key for Bearer token authentication (supports both Authorization header and X-Talabat-API-Key)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
