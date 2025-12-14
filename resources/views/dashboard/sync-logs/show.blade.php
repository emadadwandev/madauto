<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Sync Log Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('sync-logs.index', ['subdomain' => request()->route('subdomain')]) }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Sync Logs</a>
            </div>

            <!-- Log Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Log Information</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if(isset($syncLog->order_id))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Type</dt>
                                <dd class="mt-1">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Order Sync
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Order ID</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $syncLog->order->careem_order_id ?? 'N/A' }}</dd>
                            </div>
                        @elseif(isset($syncLog->menu_id))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Type</dt>
                                <dd class="mt-1">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                        Menu Sync
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Menu Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $syncLog->menu->name ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Platform</dt>
                                <dd class="mt-1">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $syncLog->platform === 'careem' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">
                                        {{ ucfirst($syncLog->platform) }}
                                    </span>
                                </dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Action</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $syncLog->action }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $syncLog->status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($syncLog->status) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Date/Time</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $syncLog->created_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Message</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $syncLog->message }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- API Response (for Menu Sync) -->
            @if ($syncLog->metadata && isset($syncLog->metadata['api_response']))
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        API Response
                    </h3>
                    <div class="space-y-4">
                        @if(isset($syncLog->metadata['api_response']['http_status']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500">HTTP Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $syncLog->metadata['api_response']['http_status'] >= 200 && $syncLog->metadata['api_response']['http_status'] < 300 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $syncLog->metadata['api_response']['http_status'] }}
                                </span>
                            </dd>
                        </div>
                        @endif
                        @if(isset($syncLog->metadata['request_id']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Request ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $syncLog->metadata['request_id'] }}</dd>
                        </div>
                        @endif
                        @if(isset($syncLog->metadata['catalog_id']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Catalog ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $syncLog->metadata['catalog_id'] }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500 mb-2">Full Response</dt>
                            <dd class="mt-1">
                                <pre class="bg-gray-100 p-4 rounded text-xs overflow-x-auto max-h-96 overflow-y-auto">{{ json_encode($syncLog->metadata['api_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Error Details (for Failed Syncs) -->
            @if ($syncLog->status === 'failed' && $syncLog->metadata)
            <div class="bg-red-50 overflow-hidden shadow-sm sm:rounded-lg mb-4 border-l-4 border-red-500">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-red-800 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Error Details
                    </h3>
                    <div class="space-y-3">
                        @if(isset($syncLog->metadata['error']))
                        <div>
                            <dt class="text-sm font-medium text-red-700">Error Message</dt>
                            <dd class="mt-1 text-sm text-red-900 bg-red-100 p-3 rounded">{{ $syncLog->metadata['error'] }}</dd>
                        </div>
                        @endif
                        @if(isset($syncLog->metadata['error_code']))
                        <div>
                            <dt class="text-sm font-medium text-red-700">Error Code</dt>
                            <dd class="mt-1 text-sm text-red-900">{{ $syncLog->metadata['error_code'] }}</dd>
                        </div>
                        @endif
                        @if(isset($syncLog->metadata['trace']))
                        <div>
                            <dt class="text-sm font-medium text-red-700 mb-2">Stack Trace</dt>
                            <dd class="mt-1">
                                <pre class="bg-red-100 p-4 rounded text-xs overflow-x-auto max-h-64 overflow-y-auto text-red-900">{{ $syncLog->metadata['trace'] }}</pre>
                            </dd>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Metadata -->
            @if ($syncLog->metadata)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Full Metadata</h3>
                    <pre class="bg-gray-100 p-4 rounded text-sm overflow-x-auto max-h-96 overflow-y-auto">{{ json_encode($syncLog->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
            @endif

            <!-- Order Details -->
            @if ($syncLog->order)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Order Details</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Careem Order ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $syncLog->order->careem_order_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Order Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $syncLog->order->status === 'synced' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $syncLog->order->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $syncLog->order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $syncLog->order->status === 'processing' ? 'bg-blue-100 text-blue-800' : '' }}">
                                    {{ ucfirst($syncLog->order->status) }}
                                </span>
                            </dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Order Data</dt>
                            <dd class="mt-1">
                                <pre class="bg-gray-100 p-4 rounded text-sm overflow-x-auto">{{ json_encode($syncLog->order->order_data, JSON_PRETTY_PRINT) }}</pre>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
            @endif

            <!-- Loyverse Order Details -->
            @if ($syncLog->order && $syncLog->order->loyverseOrder)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Loyverse Sync Details</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Loyverse Order ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $syncLog->order->loyverseOrder->loyverse_order_id ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Receipt Number</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $syncLog->order->loyverseOrder->loyverse_receipt_number ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Sync Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $syncLog->order->loyverseOrder->sync_status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($syncLog->order->loyverseOrder->sync_status) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Synced At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $syncLog->order->loyverseOrder->synced_at ? $syncLog->order->loyverseOrder->synced_at->format('Y-m-d H:i:s') : 'N/A' }}</dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Sync Response</dt>
                            <dd class="mt-1">
                                <pre class="bg-gray-100 p-4 rounded text-sm overflow-x-auto">{{ json_encode($syncLog->order->loyverseOrder->sync_response, JSON_PRETTY_PRINT) }}</pre>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
            @endif

            <!-- Actions -->
            @if ($syncLog->status === 'failed' && $syncLog->order)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('sync-logs.retry', ['order_id' => $syncLog->order_id, 'subdomain' => request()->route('subdomain')]) }}">
                        @csrf
                        <button type="submit" class="bg-orange-500 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded">
                            Retry Sync
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
