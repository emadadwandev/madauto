<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Team Activity Feed') }}
        </h2>
    </x-slot>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Team Activity Feed</h1>
            <p class="mt-1 text-sm text-gray-500">
                Recent activities across all team members
            </p>
        </div>

        @php 
        $activities = \App\Services\UserActivityService::getActivityFeed(20);
        @endphp

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Last 24 Hours</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ $activities->getCollection()->where('created_at', '>=', now()->subDay())->count() }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Successful Logins</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ $activities->getCollection()->where('action', 'user.login')->where('created_at', '>=', now()->subDay())->count() }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                        <svg class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active Users</p>
                        <p class="text-2xl font-semibold text-gray-900">
                            {{ $activities->getCollection()->where('created_at', '>=', now()->subDay())->pluck('user_id')->unique()->count() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Feed -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Activity</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    All team activities ordered chronologically
                </p>
                
                <!-- Activity Filters -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <button onclick="filterActivities('all')" class="px-3 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800">
                        All
                    </button>
                    <button onclick="filterActivities('user.login')" class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                        Logins
                    </button>
                    <button onclick="filterActivities('user.invited')" class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                        Invitations
                    </button>
                    <button onclick="filterActivities('menu.created')" class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                        Menus
                    </button>
                    <button onclick="filterActivities('location.created')" class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                        Locations
                    </button>
                </div>
            </div>
            
            <div class="divide-y divide-gray-200" id="activityFeed">
                @foreach($activities as $activity)
                    <div class="p-4 sm:p-6 hover:bg-gray-50 activity-item" data-action="{{ $activity->action }}">
                        <div class="flex space-x-3">
                            <!-- User Avatar -->
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-600">
                                        {{ strtoupper(substr($activity->user->name ?? 'System', 0, 2)) }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Activity Content -->
                            <div class="flex-1 space-y-1">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900">
                                            <!-- User info -->
                                            <span class="font-medium">{{ $activity->user->name ?? 'System' }}</span>
                                            @if($activity->user)
                                                <span class="text-gray-500 text-xs ml-2">
                                                    {{ $activity->user->email }}
                                                </span>
                                            @endif
                                        </p>
                                        
                                        <!-- Activity description -->
                                        <p class="text-sm text-gray-900 mt-1">
                                            {{ $activity->description }}
                                        </p>
                                        
                                        <!-- Additional context -->
                                        @if($activity->properties)
                                            @if(isset($activity->properties['order_id']))
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 mt-1">
                                                    Order #{{ $activity->properties['order_id'] }}
                                                </span>
                                            @endif
                                            
                                            @if(isset($activity->properties['menu_name']))
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1">
                                                    Menu: {{ $activity->properties['menu_name'] }}
                                                </span>
                                            @endif
                                            
                                            @if(isset($activity->properties['location_name']))
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mt-1">
                                                    Location: {{ $activity->properties['location_name'] }}
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                    
                                    <!-- Timestamp -->
                                    <div class="text-right ml-4">
                                        <p class="text-xs text-gray-500">
                                            <time datetime="{{ $activity->created_at->toISOString() }}">
                                                {{ $activity->created_at->diffForHumans() }}
                                            </time>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            {{ $activity->created_at->format('M j, g:i A') }}
                                        </p>
                                    </div>
                                </div>
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

<script>
function filterActivities(action) {
    const items = document.querySelectorAll('.activity-item');
    const buttons = document.querySelectorAll('button[onclick^="filterActivities"]');
    
    // Update button styles
    buttons.forEach(btn => {
        if (btn.getAttribute('onclick').includes(action)) {
            btn.className = 'px-3 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800';
        } else {
            btn.className = 'px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800';
        }
    });
    
    // Filter activities
    items.forEach(item => {
        if (action === 'all' || item.getAttribute('data-action') === action) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>
</x-app-layout>
