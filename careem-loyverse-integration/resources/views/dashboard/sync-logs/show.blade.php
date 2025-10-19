<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Sync Log Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('sync-logs.index') }}" class="text-blue-600 hover:text-blue-900">&larr; Back to Sync Logs</a>
            </div>

            <!-- Log Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Log Information</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Order ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $syncLog->order->careem_order_id ?? 'N/A' }}</dd>
                        </div>
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

            <!-- Metadata -->
            @if ($syncLog->metadata)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Metadata</h3>
                    <pre class="bg-gray-100 p-4 rounded text-sm overflow-x-auto">{{ json_encode($syncLog->metadata, JSON_PRETTY_PRINT) }}</pre>
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
                    <form method="POST" action="{{ route('sync-logs.retry', $syncLog->order_id) }}">
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
