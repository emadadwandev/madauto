<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $user->name }}'s Activity
        </h2>
    </x-slot>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-4">
                    <li>
                        <a href="{{ route('dashboard.index', ['subdomain' => request()->route('subdomain')]) }}" class="text-gray-400 hover:text-gray-500">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <a href="{{ route('dashboard.team.index', ['subdomain' => request()->route('subdomain')]) }}" class="ml-4 text-gray-400 hover:text-gray-500">
                                Team
                            </a>
                        </div>
                    </li>
                    <li class="flex items-center">
                        <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="ml-4 text-gray-500 font-medium truncate">{{ $user->name }}</span>
                    </li>
                </ol>
            </nav>
            
            <div class="mt-2">
                <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                        <span class="text-sm font-medium text-gray-600">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </span>
                    </div>
                    {{ $user->name }}'s Activity
                    <span class="ml-3 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                        {{ $user->hasRole('tenant_admin', tenant()) ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $user->hasRole('tenant_admin', tenant()) ? 'Admin' : 'User' }}
                    </span>
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $user->email }} • Last login: {{ $user->last_login_at?->diffForHumans() ?? 'Never' }}
                </p>
            </div>
        </div>

        <!-- Activity Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm font-medium text-gray-500">Last 24 Hours</p>
                <p class="text-2xl font-semibold text-gray-900">
                    {{ $activities->getCollection()->where('created_at', '>=', now()->subDay())->count() }}
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm font-medium text-gray-500">Last 7 Days</p>
                <p class="text-2xl font-semibold text-gray-900">
                    {{ $activities->getCollection()->where('created_at', '>=', now()->subWeek())->count() }}
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm font-medium text-gray-500">Total Activities</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $activities->total() }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm font-medium text-gray-500">Most Active Day</p>
                <p class="text-2xl font-semibold text-gray-900">Today</p>
            </div>
        </div>

        <!-- Activity Feed -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Activities</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Timeline of user actions and system events.
                </p>
            </div>
            
            <div class="divide-y divide-gray-200">
                @foreach($activities as $activity)
                    <div class="p-4 sm:p-6 hover:bg-gray-50">
                        <div class="flex space-x-3">
                            <!-- Activity Icon -->
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-{{ $activity->color }}-100 flex items-center justify-center">
                                    <!-- Dynamic icon based on activity -->
                                    @switch($activity->action)
                                        @case('user.login')
                                            <svg class="h-6 w-6 text-{{ $activity->color }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.414-5.414a6 6 0 017.743-1.743z" />
                                            </svg>
                                            @break
                                        @case('user.logout')
                                            <svg class="h-6 w-6 text-{{ $activity->color }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            @break
                                        @case('user.invited')
                                        @case('invitation.resent')
                                            <svg class="h-6 w-6 text-{{ $activity->color }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            @break
                                        @case('user.accepted_invitation')
                                            <svg class="h-6 w-6 text-{{ $activity->color }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            @break
                                        @case('user.role_changed')
                                            <svg class="h-6 w-6 text-{{ $activity->color }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                            @break
                                        @case('user.removed_from_tenant')
                                            <svg class="h-6 w-6 text-{{ $activity->color }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            @break
                                        @case('order.processed')
                                            <svg class="h-6 w-6 text-{{ $activity->color }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                            </svg>
                                            @break
                                        @case('menu.created')
                                            <svg class="h-6 w-6 text-{{ $activity->color }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            @break
                                        @case('menu.updated')
                                            <svg class="h-6 w-6 text-{{ $activity->color }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            @break
                                        @case('location.created')
                                            <svg class="h-6 w-6 text-{{ $activity->color }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            @break
                                        @default
                                            <svg class="h-6 w-6 text-{{ $activity->color }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                    @endswitch
                                </div>
                            </div>
                            
                            <!-- Activity Content -->
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm text-gray-900">
                                        {{ $activity->description }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <time datetime="{{ $activity->created_at->toISOString() }}">
                                            {{ $activity->created_at->format('M j, Y g:i A') }}
                                        </time>
                                    </p>
                                </div>
                                
                                <!-- Additional Details -->
                                @if($activity->properties && isset($activity->properties['ip_address']))
                                    <p class="text-xs text-gray-500">
                                        IP: {{ $activity->properties['ip_address'] }} 
                                        @if($activity->user_agent)
                                            • {{ Str::limit($activity->user_agent, 60) }}
                                        @endif
                                    </p>
                                @endif
                                
                                <!-- Causer Information -->
                                @if($activity->causer && $activity->causer->id !== $activity->user_id)
                                    <p class="text-xs text-gray-500">
                                        By: {{ $activity->causer->name }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            @if($activities->hasPages())
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    {{ $activities->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
