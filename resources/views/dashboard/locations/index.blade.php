<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Locations
            </h2>
            <a href="{{ route('dashboard.locations.create', ['subdomain' => request()->route('subdomain')]) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Location
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if (session('success'))
                <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 border border-green-200">
                    <p class="text-sm text-green-800">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Locations Grid -->
            @if($locations->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($locations as $location)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                            <div class="p-6">
                                <!-- Location Header -->
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $location->name }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">{{ $location->city }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $location->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $location->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $location->is_busy ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $location->is_busy ? 'Busy' : 'Available' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Location Details -->
                                <div class="space-y-2 mb-4 text-sm">
                                    <div class="flex items-center text-gray-600">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ $location->full_address }}
                                    </div>
                                    @if($location->phone)
                                        <div class="flex items-center text-gray-600">
                                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 00.948.684l1.498 4.493a1 1 0 00.502.756l2.048 1.024a1 1 0 00.945 0l2.048-1.024a1 1 0 00.502-.756l1.498-4.493a1 1 0 00-.948-.684H19a2 2 0 012 2v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5z" />
                                            </svg>
                                            {{ $location->phone }}
                                        </div>
                                    @endif
                                    @if($location->email)
                                        <div class="flex items-center text-gray-600">
                                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            {{ $location->email }}
                                        </div>
                                    @endif
                                </div>

                                <!-- Platforms -->
                                @if($location->platforms)
                                    <div class="mb-4">
                                        <div class="text-xs font-medium text-gray-700 mb-2">Platforms:</div>
                                        <div class="flex gap-2">
                                            @foreach($location->platforms as $platform)
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                                    {{ ucfirst($platform) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Today's Hours -->
                                @if($location->today_hours)
                                    <div class="mb-4 p-3 bg-gray-50 rounded">
                                        <div class="text-xs font-medium text-gray-700">Today's Hours</div>
                                        <div class="text-sm text-gray-900 font-semibold">
                                            {{ $location->today_hours['open'] }} - {{ $location->today_hours['close'] }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $location->isOpenNow() ? '✓ Currently Open' : '✗ Currently Closed' }}
                                        </div>
                                    </div>
                                @endif

                                <!-- Actions -->
                                <div class="flex gap-2 pt-4 border-t border-gray-200">
                                    <a href="{{ route('dashboard.locations.edit', ['location' => $location, 'subdomain' => request()->route('subdomain')]) }}"
                                       class="flex-1 text-center px-3 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded">
                                        Edit
                                    </a>
                                    <button type="button"
                                            onclick="toggleBusy({{ $location->id }}, this)"
                                            class="flex-1 px-3 py-2 text-sm font-medium rounded {{ $location->is_busy ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                                        {{ $location->is_busy ? 'Mark Available' : 'Mark Busy' }}
                                    </button>
                                    <button type="button"
                                            onclick="deleteLocation({{ $location->id }}, this)"
                                            class="flex-1 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $locations->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No locations yet</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by adding your first location.</p>
                        <div class="mt-6">
                            <a href="{{ route('dashboard.locations.create', ['subdomain' => request()->route('subdomain')]) }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create Location
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function toggleBusy(locationId, button) {
            const subdomain = '{{ request()->route("subdomain") }}';
            fetch(`/${subdomain}/dashboard/locations/${locationId}/toggle-busy`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function deleteLocation(locationId, button) {
            if (confirm('Are you sure you want to delete this location?')) {
                const subdomain = '{{ request()->route("subdomain") }}';
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/${subdomain}/dashboard/locations/${locationId}`;
                form.innerHTML = `
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</x-app-layout>
