<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-500">Total Orders</div>
                        <div class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_orders']) }}</div>
                        <div class="text-xs text-gray-500 mt-1">All time</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-500">Synced Orders</div>
                        <div class="text-3xl font-bold text-green-600">{{ number_format($stats['synced_orders']) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Success rate: {{ $stats['sync_success_rate'] }}%</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-500">Failed Orders</div>
                        <div class="text-3xl font-bold text-red-600">{{ number_format($stats['failed_orders']) }}</div>
                        <div class="text-xs text-gray-500 mt-1">
                            @if($stats['failed_orders'] > 0)
                                <a href="{{ route('sync-logs.index', ['subdomain' => request()->route('subdomain')]) }}?status=failed" class="text-blue-600 hover:text-blue-800">View logs</a>
                            @else
                                No failures
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm text-gray-500">Today's Orders</div>
                        <div class="text-3xl font-bold text-blue-600">{{ number_format($stats['today_orders']) }}</div>
                        <div class="text-xs text-gray-500 mt-1">Pending: {{ number_format($stats['pending_orders']) }}</div>
                    </div>
                </div>
            </div>

            <!-- Secondary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-gray-500">Active Product Mappings</div>
                                <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['product_mappings']) }}</div>
                            </div>
                            <div>
                                <a href="{{ route('product-mappings.index', ['subdomain' => request()->route('subdomain')]) }}" class="text-blue-600 hover:text-blue-800 text-sm">Manage</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Recent Orders</h3>
                        <a href="{{ route('orders.index', ['subdomain' => request()->route('subdomain')]) }}" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loyverse ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($recentOrders as $order)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($order->platform === 'careem')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    Careem
                                                </span>
                                            @elseif($order->platform === 'talabat')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">
                                                    Talabat
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    {{ ucfirst($order->platform) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $order->careem_order_id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                {{ $order->status === 'synced' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $order->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $order->status === 'processing' ? 'bg-blue-100 text-blue-800' : '' }}">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $order->loyverseOrder->loyverse_order_id ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $order->created_at->format('Y-m-d H:i:s') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            No orders yet. Waiting for webhook data from delivery platforms.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Sync Logs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Recent Sync Activity</h3>
                        <a href="{{ route('sync-logs.index', ['subdomain' => request()->route('subdomain')]) }}" class="text-blue-600 hover:text-blue-800 text-sm">View All Logs</a>
                    </div>

                    <div class="space-y-3">
                        @forelse ($recentLogs as $log)
                            <div class="flex items-center justify-between border-l-4 {{ $log->status === 'success' ? 'border-green-500' : 'border-red-500' }} bg-gray-50 p-3 rounded">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        @if($log->order)
                                            @if($log->order->platform === 'careem')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    Careem
                                                </span>
                                            @elseif($log->order->platform === 'talabat')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">
                                                    Talabat
                                                </span>
                                            @endif
                                        @endif
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $log->order->careem_order_id ?? 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $log->message }}</div>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $log->created_at->diffForHumans() }}
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-gray-500 py-4">
                                No sync activity yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
