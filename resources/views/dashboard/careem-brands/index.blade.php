<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Careem Brands
                </h2>
                <p class="mt-1 text-sm text-gray-600">Manage your restaurant brands for Careem integration</p>
            </div>
            <a href="{{ route('dashboard.careem-brands.create', ['subdomain' => request()->route('subdomain')]) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Brand
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
                        <p class="font-semibold mb-1">About Careem Brands</p>
                        <p>A brand represents your restaurant business (e.g., KFC, Subway). Each brand can have multiple branches (outlets). After creating a brand, you must sync it with Careem before creating branches.</p>
                    </div>
                </div>
            </div>

            <!-- Brands Grid -->
            @if($brands->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($brands as $brand)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                            <div class="p-6">
                                <!-- Brand Header -->
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $brand->name }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">ID: {{ $brand->careem_brand_id }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($brand->state === 'MAPPED') bg-green-100 text-green-800
                                        @else bg-yellow-100 text-yellow-800 @endif">
                                        {{ $brand->state }}
                                    </span>
                                </div>

                                <!-- Brand Details -->
                                <div class="space-y-2 mb-4 text-sm">
                                    <div class="flex items-center justify-between text-gray-600">
                                        <span class="font-medium">Branches:</span>
                                        <span class="font-semibold text-gray-900">{{ $brand->branches_count }}</span>
                                    </div>
                                    @if($brand->synced_at)
                                        <div class="flex items-center justify-between text-gray-600">
                                            <span class="font-medium">Last Synced:</span>
                                            <span class="text-xs">{{ $brand->synced_at->diffForHumans() }}</span>
                                        </div>
                                    @else
                                        <div class="text-xs text-yellow-600 font-medium">
                                            ⚠ Not synced with Careem yet
                                        </div>
                                    @endif
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex gap-2">
                                    <a href="{{ route('dashboard.careem-brands.edit', ['subdomain' => request()->route('subdomain'), 'careemBrand' => $brand->id]) }}"
                                       class="flex-1 text-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs font-medium">
                                        Edit
                                    </a>

                                    <form action="{{ route('dashboard.careem-brands.sync', ['subdomain' => request()->route('subdomain'), 'careemBrand' => $brand->id]) }}" method="POST" class="flex-1">
                                        @csrf
                                        <button type="submit"
                                                class="w-full px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-medium">
                                            Sync
                                        </button>
                                    </form>
                                </div>

                                <!-- Advanced Actions (collapsed by default) -->
                                <details class="mt-3">
                                    <summary class="cursor-pointer text-xs text-gray-500 hover:text-gray-700 font-medium">
                                        More Actions
                                    </summary>
                                    <div class="mt-2 space-y-2">
                                        <form action="{{ route('dashboard.careem-brands.fetch', ['subdomain' => request()->route('subdomain'), 'careemBrand' => $brand->id]) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                    class="w-full px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded text-xs font-medium">
                                                Fetch from Careem
                                            </button>
                                        </form>

                                        @if($brand->branches_count === 0)
                                            <form action="{{ route('dashboard.careem-brands.destroy', ['subdomain' => request()->route('subdomain'), 'careemBrand' => $brand->id]) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Are you sure you want to delete this brand?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="w-full px-3 py-2 bg-red-50 hover:bg-red-100 text-red-700 rounded text-xs font-medium">
                                                    Delete
                                                </button>
                                            </form>

                                            @if($brand->state === 'MAPPED')
                                                <form action="{{ route('dashboard.careem-brands.delete-from-careem', ['subdomain' => request()->route('subdomain'), 'careemBrand' => $brand->id]) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('Are you sure you want to delete this brand from Careem? This cannot be undone.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="w-full px-3 py-2 bg-red-50 hover:bg-red-100 text-red-700 rounded text-xs font-medium">
                                                        Delete from Careem
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <div class="text-xs text-gray-500 p-2 bg-gray-50 rounded">
                                                Delete all branches before deleting brand
                                            </div>
                                        @endif
                                    </div>
                                </details>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $brands->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No brands yet</h3>
                    <p class="mt-2 text-sm text-gray-500">Get started by creating your first brand for Careem integration.</p>
                    <div class="mt-6">
                        <a href="{{ route('dashboard.careem-brands.create', ['subdomain' => request()->route('subdomain')]) }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Create Brand
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
