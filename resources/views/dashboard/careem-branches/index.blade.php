<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Careem Branches
                </h2>
                <p class="mt-1 text-sm text-gray-600">Manage your restaurant outlets/locations for Careem delivery</p>
            </div>
            <a href="{{ route('dashboard.careem-branches.create', ['subdomain' => request()->route('subdomain')]) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Branch
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success/Error Messages -->
            @if (session('success'))
                <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200">
                    <p class="text-sm text-green-800">{{ session('success') }}</p>
                </div>
            @endif
            @if (session('warning'))
                <div class="mb-4 px-4 py-3 rounded-lg bg-yellow-50 border border-yellow-200">
                    <p class="text-sm text-yellow-800">{{ session('warning') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200">
                    <p class="text-sm text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            <!-- Info Box -->
            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-blue-700">
                        <p class="font-semibold mb-1">About Careem Branches</p>
                        <p>A branch represents a physical outlet of your brand (e.g., "KFC, Marina Mall"). Each branch can be mapped to a local location and has POS integration and visibility controls.</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-6 bg-white rounded-lg shadow-sm p-4">
                <form method="GET" action="{{ route('dashboard.careem-branches.index', ['subdomain' => request()->route('subdomain')]) }}" class="flex flex-wrap gap-4">
                    <!-- Brand Filter -->
                    <div class="flex-1 min-w-[200px]">
                        <label for="brand_id" class="block text-xs font-medium text-gray-700 mb-1">Filter by Brand</label>
                        <select name="brand_id" id="brand_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All Brands</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                    {{ $brand->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- State Filter -->
                    <div class="flex-1 min-w-[200px]">
                        <label for="state" class="block text-xs font-medium text-gray-700 mb-1">Filter by State</label>
                        <select name="state" id="state" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All States</option>
                            <option value="UNMAPPED" {{ request('state') == 'UNMAPPED' ? 'selected' : '' }}>Unmapped</option>
                            <option value="MAPPED" {{ request('state') == 'MAPPED' ? 'selected' : '' }}>Mapped</option>
                        </select>
                    </div>

                    <!-- POS Integration Filter -->
                    <div class="flex-1 min-w-[200px]">
                        <label for="pos_integration" class="block text-xs font-medium text-gray-700 mb-1">POS Integration</label>
                        <select name="pos_integration" id="pos_integration" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All</option>
                            <option value="1" {{ request('pos_integration') === '1' ? 'selected' : '' }}>Enabled</option>
                            <option value="0" {{ request('pos_integration') === '0' ? 'selected' : '' }}>Disabled</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm font-medium">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Branches Table -->
            @if($branches->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">State</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">POS</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visibility</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Sync</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($branches as $branch)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $branch->name }}</div>
                                            <div class="text-xs text-gray-500">ID: {{ $branch->careem_branch_id }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $branch->brand->name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($branch->state === 'MAPPED') bg-green-100 text-green-800
                                                @else bg-yellow-100 text-yellow-800 @endif">
                                                {{ $branch->state }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <form action="{{ route('dashboard.careem-branches.toggle-pos', ['subdomain' => request()->route('subdomain'), 'careemBranch' => $branch->id]) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 {{ $branch->pos_integration_enabled ? 'bg-indigo-600' : 'bg-gray-200' }}">
                                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $branch->pos_integration_enabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($branch->isActive()) bg-green-100 text-green-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ $branch->visibility_status_label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($branch->location)
                                                <div class="flex items-center text-xs">
                                                    <svg class="w-3 h-3 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                    <span class="text-gray-900">{{ $branch->location->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">Not mapped</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                            @if($branch->synced_at)
                                                {{ $branch->synced_at->diffForHumans() }}
                                            @else
                                                <span class="text-yellow-600">Never</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end gap-2">
                                                <a href="{{ route('dashboard.careem-branches.edit', ['subdomain' => request()->route('subdomain'), 'careemBranch' => $branch->id]) }}"
                                                   class="text-indigo-600 hover:text-indigo-900">
                                                    Edit
                                                </a>
                                                
                                                <form action="{{ route('dashboard.careem-branches.sync', ['subdomain' => request()->route('subdomain'), 'careemBranch' => $branch->id]) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-blue-600 hover:text-blue-900">
                                                        Sync
                                                    </button>
                                                </form>

                                                <details class="relative inline-block text-left">
                                                    <summary class="cursor-pointer text-gray-600 hover:text-gray-900">
                                                        More
                                                    </summary>
                                                    <div class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5">
                                                        <div class="py-1">
                                                            <form action="{{ route('dashboard.careem-branches.fetch', ['subdomain' => request()->route('subdomain'), 'careemBranch' => $branch->id]) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">
                                                                    Fetch from Careem
                                                                </button>
                                                            </form>
                                                            
                                                            <a href="{{ route('dashboard.careem-branches.temporary-status', ['subdomain' => request()->route('subdomain'), 'careemBranch' => $branch->id]) }}"
                                                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                                Set Temporary Closure
                                                            </a>
                                                            
                                                            <form action="{{ route('dashboard.careem-branches.destroy', ['subdomain' => request()->route('subdomain'), 'careemBranch' => $branch->id]) }}"
                                                                  method="POST"
                                                                  onsubmit="return confirm('Are you sure you want to delete this branch?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-700 hover:bg-gray-100">
                                                                    Delete
                                                                </button>
                                                            </form>

                                                            @if($branch->state === 'MAPPED')
                                                                <form action="{{ route('dashboard.careem-branches.delete-from-careem', ['subdomain' => request()->route('subdomain'), 'careemBranch' => $branch->id]) }}"
                                                                      method="POST"
                                                                      onsubmit="return confirm('Delete from Careem? This cannot be undone.')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-700 hover:bg-gray-100">
                                                                        Delete from Careem
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </details>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($branches->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $branches->links() }}
                        </div>
                    @endif
                </div>
            @else
                <!-- Empty State -->
                <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No branches</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new branch for your brand.</p>
                    <div class="mt-6">
                        <a href="{{ route('dashboard.careem-branches.create', ['subdomain' => request()->route('subdomain')]) }}"
                           class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add First Branch
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
