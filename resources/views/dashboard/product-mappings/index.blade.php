<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Product Mappings') }}
            </h2>
            <a href="{{ route('product-mappings.create', ['subdomain' => request()->route('subdomain')]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add New Mapping
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('product-mappings.auto-map', ['subdomain' => request()->route('subdomain')]) }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Auto-Map by SKU
                            </button>
                        </form>

                        <a href="{{ route('product-mappings.export', ['subdomain' => request()->route('subdomain')]) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded inline-block">
                            Export to CSV
                        </a>

                        <button onclick="document.getElementById('importForm').classList.toggle('hidden')" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                            Import from CSV
                        </button>

                        <form method="POST" action="{{ route('product-mappings.clear-cache', ['subdomain' => request()->route('subdomain')]) }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                Clear Cache
                            </button>
                        </form>
                    </div>

                    <!-- Import Form (Hidden by default) -->
                    <div id="importForm" class="hidden mt-4">
                        <form method="POST" action="{{ route('product-mappings.import', ['subdomain' => request()->route('subdomain')]) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="flex items-center gap-2">
                                <input type="file" name="csv_file" accept=".csv,.txt" required class="border rounded px-3 py-2">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Upload
                                </button>
                                <button type="button" onclick="document.getElementById('importForm').classList.add('hidden')" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <form method="GET" action="{{ route('product-mappings.index', ['subdomain' => request()->route('subdomain')]) }}" class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <input type="text" name="search" placeholder="Search by name, SKU, or product ID" value="{{ request('search') }}" class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <select name="platform" class="border rounded px-3 py-2">
                                <option value="">All Platforms</option>
                                <option value="careem" {{ request('platform') === 'careem' ? 'selected' : '' }}>Careem</option>
                                <option value="talabat" {{ request('platform') === 'talabat' ? 'selected' : '' }}>Talabat</option>
                            </select>
                        </div>
                        <div>
                            <select name="status" class="border rounded px-3 py-2">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Filter
                        </button>
                        <a href="{{ route('product-mappings.index', ['subdomain' => request()->route('subdomain')]) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded inline-block">
                            Reset
                        </a>
                    </form>
                </div>
            </div>

            <!-- Mappings Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Platform</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loyverse Item ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($mappings as $mapping)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($mapping->platform === 'careem')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    Careem
                                                </span>
                                            @elseif($mapping->platform === 'talabat')
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">
                                                    Talabat
                                                </span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    {{ ucfirst($mapping->platform) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $mapping->platform_name }}</div>
                                            <div class="text-sm text-gray-500">ID: {{ $mapping->platform_product_id }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $mapping->platform_sku ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $mapping->loyverse_item_id }}
                                            @if($mapping->loyverse_variant_id)
                                                <br><span class="text-xs">Variant: {{ $mapping->loyverse_variant_id }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <form method="POST" action="{{ route('product-mappings.toggle', ['id' => $mapping->id, 'subdomain' => request()->route('subdomain')]) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $mapping->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $mapping->is_active ? 'Active' : 'Inactive' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('product-mappings.edit', ['id' => $mapping->id, 'subdomain' => request()->route('subdomain')]) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                            <form method="POST" action="{{ route('product-mappings.destroy', ['id' => $mapping->id, 'subdomain' => request()->route('subdomain')]) }}" class="inline" onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No product mappings found. <a href="{{ route('product-mappings.create', ['subdomain' => request()->route('subdomain')]) }}" class="text-blue-600">Create one</a> or try auto-mapping.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if ($mappings->hasPages())
                        <div class="mt-4">
                            {{ $mappings->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
