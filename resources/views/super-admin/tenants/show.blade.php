<x-super-admin-layout>
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center">
                    <a href="{{ route('super-admin.tenants.index') }}" class="mr-3 text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <h1 class="text-2xl font-semibold text-gray-900">{{ $tenant->name }}</h1>
                </div>
                <p class="mt-1 text-sm text-gray-500">Tenant details and statistics</p>
            </div>
            <div class="mt-4 flex gap-3 sm:mt-0">
                <a href="{{ route('super-admin.tenants.edit', $tenant) }}"
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Edit
                </a>
                <form method="POST" action="{{ route('super-admin.tenants.impersonate', $tenant) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Impersonate
                    </button>
                </form>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Orders</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['total_orders'] }}</dd>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">This Month</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['orders_this_month'] }}</dd>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">Users</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['total_users'] }}</dd>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dt class="text-sm font-medium text-gray-500 truncate">Product Mappings</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['product_mappings'] }}</dd>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Tenant Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Tenant Information</h3>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->name }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Subdomain</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->subdomain }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                @if($tenant->status === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @elseif($tenant->status === 'suspended')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Suspended
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Trialing
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->created_at->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Trial Ends</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($tenant->trial_ends_at)
                                    {{ $tenant->trial_ends_at->format('M d, Y') }}
                                    @if($tenant->trial_ends_at->isFuture())
                                        <span class="text-xs text-green-600">({{ $tenant->trial_ends_at->diffForHumans() }})</span>
                                    @else
                                        <span class="text-xs text-red-600">(Expired)</span>
                                    @endif
                                @else
                                    N/A
                                @endif
                            </dd>
                        </div>
                    </dl>

                    <!-- Actions -->
                    <div class="mt-6 flex gap-3">
                        @if($tenant->status === 'active')
                        <form method="POST" action="{{ route('super-admin.tenants.suspend', $tenant) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('Are you sure you want to suspend this tenant?')"
                                    class="inline-flex items-center px-3 py-2 border border-orange-300 rounded-md text-sm font-medium text-orange-700 bg-white hover:bg-orange-50">
                                Suspend Tenant
                            </button>
                        </form>
                        @elseif($tenant->status === 'suspended')
                        <form method="POST" action="{{ route('super-admin.tenants.activate', $tenant) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-2 border border-green-300 rounded-md text-sm font-medium text-green-700 bg-white hover:bg-green-50">
                                Activate Tenant
                            </button>
                        </form>
                        @endif

                        <form method="POST" action="{{ route('super-admin.tenants.destroy', $tenant) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    onclick="return confirm('Are you sure you want to delete this tenant? This action cannot be undone.')"
                                    class="inline-flex items-center px-3 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                                Delete Tenant
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Subscription Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Subscription</h3>
                    @if($tenant->subscription)
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Plan</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->subscription->subscriptionPlan->name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $tenant->subscription->status === 'active' ? 'bg-green-100 text-green-800' :
                                       ($tenant->subscription->status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                    {{ ucfirst($tenant->subscription->status) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Started</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->subscription->created_at->format('M d, Y') }}</dd>
                        </div>
                        @if($tenant->subscription->ends_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Ends</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $tenant->subscription->ends_at->format('M d, Y') }}</dd>
                        </div>
                        @endif
                    </dl>
                    <div class="mt-6">
                        <a href="{{ route('super-admin.subscriptions.show', $tenant->subscription) }}"
                           class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            View Subscription Details
                        </a>
                    </div>
                    @else
                    <p class="text-sm text-gray-500">No active subscription</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Orders</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Order ID
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Platform
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Created
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($stats['recent_orders'] as $order)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $order->careem_order_id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ ucfirst($order->platform) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ ucfirst($order->status) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $order->created_at->format('M d, Y H:i') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No orders found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-super-admin-layout>
