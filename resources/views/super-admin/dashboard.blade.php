<x-super-admin-layout>
    <div class="space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Welcome to the Super Admin panel. Monitor your platform's health and performance.</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Tenants -->
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500">Total Tenants</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ $stats['total_tenants'] }}</dd>
            </div>

            <!-- Active Tenants -->
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500">Active Tenants</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-green-600">{{ $stats['active_tenants'] }}</dd>
            </div>

            <!-- Active Subscriptions -->
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500">Active Subscriptions</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-blue-600">{{ $stats['active_subscriptions'] }}</dd>
            </div>

            <!-- MRR -->
            <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <dt class="truncate text-sm font-medium text-gray-500">Monthly Recurring Revenue</dt>
                <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">${{ number_format($stats['mrr'], 2) }}</dd>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <!-- Revenue Chart -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Monthly Revenue (Last 12 Months)</h3>
                    <div class="mt-6">
                        <canvas id="revenueChart" class="w-full" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tenant Growth Chart -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Tenant Growth (Last 12 Months)</h3>
                    <div class="mt-6">
                        <canvas id="tenantGrowthChart" class="w-full" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Volume Chart -->
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-base font-semibold leading-6 text-gray-900">Order Volume (Last 30 Days)</h3>
                <div class="mt-6">
                    <canvas id="orderVolumeChart" class="w-full" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activity & Tenants with Issues -->
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <!-- Recent Activity -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4">Recent Activity</h3>
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            @forelse($recentActivity as $index => $activity)
                            <li>
                                <div class="relative pb-8">
                                    @if(!$loop->last)
                                    <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                                <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div>
                                                <p class="text-sm text-gray-500">{{ $activity->description }}</p>
                                            </div>
                                            <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                                <time datetime="{{ $activity->created_at }}">{{ $activity->created_at->diffForHumans() }}</time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            @empty
                            <li class="text-sm text-gray-500">No recent activity</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Tenants with Issues -->
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4">Tenants Requiring Attention</h3>
                    <div class="flow-root">
                        <ul role="list" class="divide-y divide-gray-200">
                            @forelse($tenantsWithIssues as $tenant)
                            <li class="py-3">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $tenant->name }}
                                        </p>
                                        <p class="text-sm text-gray-500 truncate">
                                            @if($tenant->subscription->status === 'past_due')
                                                <span class="text-red-600">Payment past due</span>
                                            @elseif($tenant->subscription->status === 'cancelled')
                                                <span class="text-orange-600">Subscription cancelled</span>
                                            @else
                                                <span class="text-yellow-600">Trial ending soon</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div>
                                        <a href="{{ route('super-admin.tenants.show', $tenant) }}" class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                            View
                                        </a>
                                    </div>
                                </div>
                            </li>
                            @empty
                            <li class="text-sm text-gray-500 py-3">No tenants requiring attention</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: @json($revenueChartData['labels']),
                datasets: [{
                    label: 'Revenue',
                    data: @json($revenueChartData['data']),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Order Volume Chart
        const orderVolumeCtx = document.getElementById('orderVolumeChart');
        new Chart(orderVolumeCtx, {
            type: 'bar',
            data: {
                labels: @json($orderVolumeData['labels']),
                datasets: [{
                    label: 'Orders',
                    data: @json($orderVolumeData['data']),
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Tenant Growth Chart
        const tenantGrowthCtx = document.getElementById('tenantGrowthChart');
        new Chart(tenantGrowthCtx, {
            type: 'line',
            data: {
                labels: @json($tenantGrowthData['labels']),
                datasets: [{
                    label: 'New Tenants',
                    data: @json($tenantGrowthData['data']),
                    borderColor: 'rgb(168, 85, 247)',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
    @endpush
</x-super-admin-layout>
