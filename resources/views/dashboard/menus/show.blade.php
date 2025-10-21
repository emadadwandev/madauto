<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $menu->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('dashboard.menus.edit', ['menu' => $menu, 'subdomain' => request()->route('subdomain')]) }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Menu
                </a>
                <a href="{{ route('dashboard.menus.index', ['subdomain' => request()->route('subdomain')]) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Menus
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Menu Header Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex gap-6">
                        <!-- Menu Image -->
                        <div class="flex-shrink-0">
                            <div class="w-48 h-48 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-lg overflow-hidden">
                                @if($menu->image_url)
                                    <img src="{{ Storage::url($menu->image_url) }}" alt="{{ $menu->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="flex items-center justify-center h-full">
                                        <svg class="w-20 h-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Menu Details -->
                        <div class="flex-1">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900">{{ $menu->name }}</h3>
                                    @if($menu->description)
                                        <p class="mt-2 text-gray-600">{{ $menu->description }}</p>
                                    @endif
                                </div>
                                <div class="flex gap-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $menu->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($menu->status) }}
                                    </span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $menu->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $menu->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Stats Grid -->
                            <div class="grid grid-cols-3 gap-4 mt-6">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="text-sm text-gray-500">Total Items</div>
                                    <div class="text-2xl font-bold text-gray-900">{{ $menu->items->count() }}</div>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="text-sm text-gray-500">Locations</div>
                                    <div class="text-2xl font-bold text-gray-900">{{ $menu->locations->count() }}</div>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="text-sm text-gray-500">Platforms</div>
                                    <div class="text-2xl font-bold text-gray-900">{{ count($menu->platforms()) }}</div>
                                </div>
                            </div>

                            <!-- Locations & Platforms -->
                            <div class="mt-6 grid grid-cols-2 gap-4">
                                <!-- Assigned Locations -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Assigned Locations:</h4>
                                    @if($menu->locations->count() > 0)
                                        <div class="space-y-1">
                                            @foreach($menu->locations as $location)
                                                <div class="flex items-center text-sm text-gray-600">
                                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    {{ $location->name }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500">No locations assigned</p>
                                    @endif
                                </div>

                                <!-- Assigned Platforms -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Assigned Platforms:</h4>
                                    @if(count($menu->platforms()) > 0)
                                        <div class="space-y-1">
                                            @foreach($menu->platforms() as $platform)
                                                <div class="flex items-center text-sm text-gray-600">
                                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    {{ ucfirst($platform) }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500">No platforms assigned</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu Items -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Menu Items</h3>

                    @if($menu->items->count() > 0)
                        @php
                            $itemsByCategory = $menu->items->groupBy('category');
                        @endphp

                        @foreach($itemsByCategory as $category => $items)
                            <div class="mb-6">
                                <h4 class="text-md font-medium text-gray-700 mb-3 pb-2 border-b border-gray-200">
                                    {{ $category ?: 'Uncategorized' }}
                                </h4>

                                <div class="space-y-3">
                                    @foreach($items as $item)
                                        <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                            <!-- Item Image -->
                                            <div class="flex-shrink-0">
                                                <div class="w-16 h-16 bg-gradient-to-br from-indigo-100 to-purple-100 rounded overflow-hidden">
                                                    @if($item->image_url)
                                                        <img src="{{ Storage::url($item->image_url) }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                                                    @else
                                                        <div class="flex items-center justify-center h-full">
                                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Item Details -->
                                            <div class="flex-1">
                                                <div class="flex items-start justify-between">
                                                    <div>
                                                        <h5 class="text-sm font-medium text-gray-900">{{ $item->name }}</h5>
                                                        @if($item->description)
                                                            <p class="text-xs text-gray-600 mt-1">{{ $item->description }}</p>
                                                        @endif
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="text-sm font-semibold text-gray-900">AED {{ number_format($item->price, 2) }}</div>
                                                        @if($item->tax_rate > 0)
                                                            <div class="text-xs text-gray-500">+{{ $item->tax_rate }}% tax</div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Modifier Groups -->
                                                @if($item->modifierGroups->count() > 0)
                                                    <div class="mt-2">
                                                        <div class="flex flex-wrap gap-1">
                                                            @foreach($item->modifierGroups as $group)
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                                                    {{ $group->name }}
                                                                    @if($group->is_required)
                                                                        <span class="ml-1 text-red-600">*</span>
                                                                    @endif
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                <!-- Item Status -->
                                                <div class="mt-2 flex items-center gap-2 text-xs">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full {{ $item->is_available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ $item->is_available ? 'Available' : 'Out of Stock' }}
                                                    </span>
                                                    @if($item->sku)
                                                        <span class="text-gray-500">SKU: {{ $item->sku }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No items yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by adding items to this menu.</p>
                            <div class="mt-6">
                                <a href="{{ route('dashboard.menus.edit', ['menu' => $menu, 'subdomain' => request()->route('subdomain')]) }}"
                                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Items
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
