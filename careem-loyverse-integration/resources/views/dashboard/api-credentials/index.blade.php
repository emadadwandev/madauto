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

                    <form method="POST" action="{{ route('api-credentials.store') }}" class="space-y-4">
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
                        <form method="POST" action="{{ route('api-credentials.test-connection') }}">
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

                    <form method="POST" action="{{ route('api-credentials.store') }}" class="space-y-4">
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
                            <code class="flex-1 text-sm">{{ url('/api/webhook/careem') }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ url('/api/webhook/careem') }}')" class="ml-2 bg-gray-500 hover:bg-gray-700 text-white font-bold py-1 px-3 rounded text-sm">
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Talabat Settings -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Talabat Webhook Settings</h3>

                    <form method="POST" action="{{ route('api-credentials.store') }}" class="space-y-4">
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
                            <code class="flex-1 text-sm">{{ url('/api/webhook/talabat') }}</code>
                            <button onclick="navigator.clipboard.writeText('{{ url('/api/webhook/talabat') }}')" class="ml-2 bg-gray-500 hover:bg-gray-700 text-white font-bold py-1 px-3 rounded text-sm">
                                Copy
                            </button>
                        </div>
                        <p class="text-gray-600 text-xs italic mt-2">
                            Send the API Key as a Bearer token: <code class="bg-gray-100 px-1 py-0.5 rounded">Authorization: Bearer YOUR_API_KEY</code><br>
                            Or as a custom header: <code class="bg-gray-100 px-1 py-0.5 rounded">X-Talabat-API-Key: YOUR_API_KEY</code>
                        </p>
                    </div>
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
                                            <form method="POST" action="{{ route('api-credentials.toggle', $credential) }}" class="inline">
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
                                            <form method="POST" action="{{ route('api-credentials.destroy', $credential) }}" class="inline" onsubmit="return confirm('Are you sure?')">
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
