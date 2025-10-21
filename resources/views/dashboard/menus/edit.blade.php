<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Menu') }}: {{ $menu->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('dashboard.menus.show', ['menu' => $menu, 'subdomain' => request()->route('subdomain')]) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Preview
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
            <!-- Success/Error Messages -->
            @if (session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Whoops! Something went wrong.</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Form (Left Column - 2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Menu Details Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Menu Details</h3>

                            <form method="POST" action="{{ route('dashboard.menus.update', ['menu' => $menu, 'subdomain' => request()->route('subdomain')]) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <!-- Menu Name -->
                                <div class="mb-4">
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Menu Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           name="name"
                                           id="name"
                                           value="{{ old('name', $menu->name) }}"
                                           required
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>

                                <!-- Description -->
                                <div class="mb-4">
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                        Description
                                    </label>
                                    <textarea name="description"
                                              id="description"
                                              rows="3"
                                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $menu->description) }}</textarea>
                                </div>

                                <!-- Current Image -->
                                @if($menu->image_url)
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Image</label>
                                        <div class="flex items-center gap-4">
                                            <img src="{{ Storage::url($menu->image_url) }}" alt="{{ $menu->name }}" class="w-32 h-32 object-cover rounded-lg">
                                            <div class="flex items-center">
                                                <input type="checkbox"
                                                       name="remove_image"
                                                       id="remove_image"
                                                       value="1"
                                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <label for="remove_image" class="ml-2 text-sm text-gray-700">
                                                    Remove image
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Image Upload -->
                                <div class="mb-4">
                                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ $menu->image_url ? 'Replace Image' : 'Upload Image' }}
                                    </label>
                                    <input type="file"
                                           name="image"
                                           id="image"
                                           accept="image/jpeg,image/jpg,image/png,image/webp"
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                    <p class="mt-1 text-xs text-gray-500">JPEG, JPG, PNG, WEBP. Max 2MB</p>
                                </div>

                                <!-- Locations -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Assign to Locations
                                    </label>
                                    <div class="space-y-2">
                                        @foreach($locations as $location)
                                            <div class="flex items-center">
                                                <input type="checkbox"
                                                       name="locations[]"
                                                       id="location_{{ $location->id }}"
                                                       value="{{ $location->id }}"
                                                       {{ in_array($location->id, old('locations', $menu->locations->pluck('id')->toArray())) ? 'checked' : '' }}
                                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <label for="location_{{ $location->id }}" class="ml-2 text-sm text-gray-700">
                                                    {{ $location->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Platforms -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Assign to Platforms
                                    </label>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <input type="checkbox"
                                                   name="platforms[]"
                                                   id="platform_careem"
                                                   value="careem"
                                                   {{ in_array('careem', old('platforms', $platforms)) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <label for="platform_careem" class="ml-2 text-sm text-gray-700">
                                                Careem NOW
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="checkbox"
                                                   name="platforms[]"
                                                   id="platform_talabat"
                                                   value="talabat"
                                                   {{ in_array('talabat', old('platforms', $platforms)) ? 'checked' : '' }}
                                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <label for="platform_talabat" class="ml-2 text-sm text-gray-700">
                                                Talabat
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Active Status -->
                                <div class="mb-4">
                                    <div class="flex items-center">
                                        <input type="checkbox"
                                               name="is_active"
                                               id="is_active"
                                               value="1"
                                               {{ old('is_active', $menu->is_active) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <label for="is_active" class="ml-2 text-sm text-gray-700">
                                            Active
                                        </label>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center justify-end gap-4 mt-6">
                                    <a href="{{ route('dashboard.menus.index', ['subdomain' => request()->route('subdomain')]) }}"
                                       class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                                        Cancel
                                    </a>
                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                        Update Menu
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Menu Items Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Menu Items ({{ $menu->items->count() }})</h3>
                                <a href="{{ route('dashboard.menus.items.create', ['menu' => $menu, 'subdomain' => request()->route('subdomain')]) }}"
                                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Item
                                </a>
                            </div>

                            @if($menu->items->count() > 0)
                                <div id="sortable-items" class="space-y-3">
                                    @foreach($menu->items->sortBy('sort_order') as $item)
                                        <div class="sortable-item bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors cursor-move" data-id="{{ $item->id }}">
                                            <div class="flex items-start gap-4">
                                                <!-- Drag Handle -->
                                                <div class="flex-shrink-0 text-gray-400 hover:text-gray-600">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                                    </svg>
                                                </div>

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
                                                            <h4 class="text-sm font-medium text-gray-900">{{ $item->name }}</h4>
                                                            @if($item->description)
                                                                <p class="text-xs text-gray-600 mt-1">{{ Str::limit($item->description, 80) }}</p>
                                                            @endif
                                                            @if($item->category)
                                                                <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-700">
                                                                    {{ $item->category }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="text-right">
                                                            <div class="text-sm font-semibold text-gray-900">AED {{ number_format($item->price, 2) }}</div>
                                                            <div class="flex items-center gap-1 mt-1">
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $item->is_available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                                    {{ $item->is_available ? 'Available' : 'Out of Stock' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Modifier Groups -->
                                                    @if($item->modifierGroups->count() > 0)
                                                        <div class="mt-2 flex flex-wrap gap-1">
                                                            @foreach($item->modifierGroups as $group)
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                                                    {{ $group->name }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    <!-- Actions -->
                                                    <div class="flex items-center gap-2 mt-3">
                                                        <form action="{{ route('dashboard.menus.items.toggle-availability', ['menu' => $menu, 'menuItem' => $item, 'subdomain' => request()->route('subdomain')]) }}" method="POST" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800">
                                                                {{ $item->is_available ? 'Mark Out of Stock' : 'Mark Available' }}
                                                            </button>
                                                        </form>
                                                        <span class="text-gray-300">|</span>
                                                        <a href="{{ route('dashboard.menus.items.edit', ['menu' => $menu, 'menuItem' => $item, 'subdomain' => request()->route('subdomain')]) }}" class="text-xs text-indigo-600 hover:text-indigo-800">
                                                            Edit
                                                        </a>
                                                        <span class="text-gray-300">|</span>
                                                        <form action="{{ route('dashboard.menus.items.duplicate', ['menu' => $menu, 'menuItem' => $item, 'subdomain' => request()->route('subdomain')]) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800">
                                                                Duplicate
                                                            </button>
                                                        </form>
                                                        <span class="text-gray-300">|</span>
                                                        <form action="{{ route('dashboard.menus.items.destroy', ['menu' => $menu, 'menuItem' => $item, 'subdomain' => request()->route('subdomain')]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-xs text-red-600 hover:text-red-800">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">No items yet</h3>
                                    <p class="mt-1 text-sm text-gray-500">Get started by adding your first menu item.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar (Right Column - 1/3) -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Menu Status Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Menu Status</h3>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Status:</span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $menu->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($menu->status) }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Active:</span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $menu->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $menu->is_active ? 'Yes' : 'No' }}
                                    </span>
                                </div>

                                @if($menu->published_at)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Published:</span>
                                        <span class="text-xs text-gray-500">{{ $menu->published_at->format('M d, Y') }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-6 space-y-2">
                                @if($menu->isDraft())
                                    <form action="{{ route('dashboard.menus.publish', ['menu' => $menu, 'subdomain' => request()->route('subdomain')]) }}" method="POST" class="w-full">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Publish Menu
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('dashboard.menus.unpublish', ['menu' => $menu, 'subdomain' => request()->route('subdomain')]) }}" method="POST" class="w-full">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700">
                                            Unpublish Menu
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats Card -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Total Items:</span>
                                    <span class="text-lg font-semibold text-gray-900">{{ $menu->items->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Available:</span>
                                    <span class="text-lg font-semibold text-green-600">{{ $menu->items->where('is_available', true)->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Out of Stock:</span>
                                    <span class="text-lg font-semibold text-red-600">{{ $menu->items->where('is_available', false)->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Locations:</span>
                                    <span class="text-lg font-semibold text-gray-900">{{ $menu->locations->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Platforms:</span>
                                    <span class="text-lg font-semibold text-gray-900">{{ count($platforms) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <!-- Sortable.js for drag-drop reordering -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sortableList = document.getElementById('sortable-items');

            if (sortableList) {
                const sortable = Sortable.create(sortableList, {
                    animation: 150,
                    handle: '.sortable-item',
                    ghostClass: 'opacity-50',
                    onEnd: function() {
                        // Get new order
                        const items = sortableList.querySelectorAll('.sortable-item');
                        const order = Array.from(items).map(item => item.dataset.id);

                        // Send AJAX request to update order
                        fetch('{{ route('dashboard.menus.items.reorder', ['menu' => $menu, 'subdomain' => request()->route('subdomain')]) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ order: order })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Order updated successfully');
                            }
                        })
                        .catch(error => {
                            console.error('Error updating order:', error);
                            alert('Failed to update item order. Please refresh the page and try again.');
                        });
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
