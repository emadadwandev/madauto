<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Team Management') }}
        </h2>
    </x-slot>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Team Management</h1>
            <p class="mt-1 text-sm text-gray-500">
                Manage team members, roles, and invitations
            </p>
        </div>

        <!-- Team Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Team Members</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $users->total() }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Admins</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $adminCount }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Team Users</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $userCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <a href="{{ route('dashboard.invitations.create', ['subdomain' => request()->route('subdomain')]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Invite Team Member
                </a>
                
                <a href="{{ route('dashboard.invitations.index', ['subdomain' => request()->route('subdomain')]) }}" 
                   class="ml-3 inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Pending Invitations
                </a>
            </div>
            
            <div>
                <a href="{{ route('dashboard.team.activity-feed', ['subdomain' => request()->route('subdomain')]) }}" 
                   class="text-sm text-indigo-600 hover:text-indigo-500">
                    View Activity Feed →
                </a>
            </div>
        </div>

        <!-- Team Members Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Team Members</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Manage roles and permissions for your team members.
                </p>
            </div>
            
            <ul class="divide-y divide-gray-200">
                @forelse($users as $teamMember)
                    <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center min-w-0 flex-1">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 flex-shrink-0 rounded-full bg-gray-200 flex items-center justify-center">
                                        <span class="text-sm font-medium leading-none text-gray-600">
                                            {{ strtoupper(substr($teamMember->name, 0, 2)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1 px-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $teamMember->name }}
                                            @if($teamMember->id === auth()->id())
                                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    You
                                                </span>
                                            @endif
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ $teamMember->email }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <!-- Role Badge -->
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                        {{ $teamMember->hasRole('tenant_admin', tenant()) ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $teamMember->hasRole('tenant_admin', tenant()) ? 'Admin' : 'User' }}
                                    </span>
                                    
                                    <!-- Activity Status -->
                                    <div class="text-sm text-gray-500">
                                        <span title="Last activity">
                                            @if($teamMember->last_login_at)
                                                <time datetime="{{ $teamMember->last_login_at->toISOString() }}">
                                                    {{ $teamMember->last_login_at->diffForHumans() }}
                                                </time>
                                            @else
                                                Never logged in
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex-shrink-0 ml-5">
                                @if($teamMember->id !== auth()->id())
                                    @can('updateUserRole', [tenant(), $teamMember])
                                        <div class="inline-flex rounded-md shadow-sm" role="group">
                                            <form action="{{ route('dashboard.team.edit-role', ['subdomain' => request()->route('subdomain'), 'user' => $teamMember->id]) }}" method="POST" class="inline-flex items-center">
                                                @csrf
                                                @method('PATCH')
                                                <select name="role" onchange="this.form.submit()" class="text-sm border-0 focus:ring-2 focus:ring-indigo-500 rounded-l-md">
                                                    <option value="tenant_admin" {{ $teamMember->hasRole('tenant_admin', tenant()) ? 'selected' : '' }}>
                                                        Admin
                                                    </option>
                                                    <option value="tenant_user" {{ $teamMember->hasRole('tenant_user', tenant()) ? 'selected' : '' }}>
                                                        User
                                                    </option>
                                                </select>
                                            </form>
                                            
                                            <div x-data="{ confirmRemove: false }" class="inline-flex">
                                                <button @click="confirmRemove = true" 
                                                        x-show="!confirmRemove"
                                                        type="button" 
                                                        class="inline-flex items-center px-3 py-2 border border-l-0 border-gray-300 text-sm leading-4 font-medium text-red-600 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 rounded-r-md">
                                                    Remove
                                                </button>
                                                
                                                <form x-show="confirmRemove" 
                                                      x-cloak
                                                      action="{{ route('dashboard.team.remove-user', ['subdomain' => request()->route('subdomain'), 'user' => $teamMember->id]) }}" 
                                                      method="POST" 
                                                      class="inline-flex items-center">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="inline-flex items-center px-3 py-2 border border-l-0 border-gray-300 text-sm leading-4 font-medium text-red-700 hover:text-red-800 bg-red-50 rounded-r-md">
                                                        Confirm Remove
                                                    </button>
                                                    <button type="button" 
                                                            @click="confirmRemove = false"
                                                            class="ml-2 text-xs text-gray-500 hover:text-gray-700">
                                                        Cancel
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endcan
                                    
                                    <!-- View Activity -->
                                    <a href="{{ route('dashboard.team.activity', ['subdomain' => request()->route('subdomain'), 'user' => $teamMember->id]) }}" 
                                       class="ml-3 text-indigo-600 hover:text-indigo-900 text-sm">
                                        Activity
                                    </a>
                                @else
                                    <span class="text-sm text-gray-500">Current user</span>
                                @endif
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No team members</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Get started by inviting team members to help manage your restaurant.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('dashboard.invitations.create', ['subdomain' => request()->route('subdomain')]) }}" 
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Invite Team Member
                            </a>
                        </div>
                    </li>
                @endforelse
            </ul>
            
            <!-- Pagination -->
            @if($users->hasPages())
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
