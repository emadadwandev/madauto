<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Branch: {{ $branch->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('dashboard.careem-branches.update', ['subdomain' => request()->route('subdomain'), 'careemBranch' => $branch->id]) }}">
                        @csrf
                        @method('PUT')

                        <!-- Brand (Read-only) -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Brand
                            </label>
                            <input type="text"
                                   value="{{ $branch->brand->name }} ({{ $branch->brand->careem_brand_id }})"
                                   disabled
                                   class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Brand cannot be changed after creation</p>
                        </div>

                        <!-- Branch ID (Read-only) -->
                        <div class="mb-4">
                            <label for="careem_branch_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Branch ID
                            </label>
                            <input type="text"
                                   value="{{ $branch->careem_branch_id }}"
                                   disabled
                                   class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Branch ID cannot be changed after creation</p>
                        </div>

                        <!-- Branch Name -->
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Branch Name *
                            </label>
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name', $branch->name) }}"
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('name') border-red-500 @enderror">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Location Mapping -->
                        <div class="mb-4">
                            <label for="location_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Map to Local Location (Optional)
                            </label>
                            <select name="location_id"
                                    id="location_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('location_id') border-red-500 @enderror">
                                <option value="">No location mapping</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ old('location_id', $branch->location_id) == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('location_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Link this branch to an existing location for order synchronization</p>
                        </div>

                        <!-- POS Integration Status -->
                        <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">POS Integration</label>
                                    <p class="text-xs text-gray-500 mt-1">Control whether this branch receives orders</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-medium {{ $branch->pos_integration_enabled ? 'text-green-700' : 'text-gray-500' }}">
                                        {{ $branch->pos_integration_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                    <form action="{{ route('dashboard.careem-branches.toggle-pos', ['subdomain' => request()->route('subdomain'), 'careemBranch' => $branch->id]) }}"
                                          method="POST"
                                          onsubmit="return confirm('Are you sure you want to {{ $branch->pos_integration_enabled ? 'disable' : 'enable' }} POS integration?')">
                                        @csrf
                                        <button type="submit"
                                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-medium">
                                            {{ $branch->pos_integration_enabled ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Visibility Status -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Visibility on Careem SuperApp
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio"
                                           name="visibility_status"
                                           value="1"
                                           {{ old('visibility_status', $branch->visibility_status) == 1 ? 'checked' : '' }}
                                           class="border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">Active - Visible to customers</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio"
                                           name="visibility_status"
                                           value="2"
                                           {{ old('visibility_status', $branch->visibility_status) == 2 ? 'checked' : '' }}
                                           class="border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">Inactive - Hidden from customers</span>
                                </label>
                            </div>
                            @error('visibility_status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Current status: <strong>{{ $branch->visibility_status_label }}</strong></p>
                        </div>

                        <!-- Temporary Status Section -->
                        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-yellow-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="flex-1">
                                    <label class="text-sm font-medium text-yellow-800">Temporary Closure</label>
                                    <p class="text-xs text-yellow-700 mt-1 mb-3">Set branch as temporarily closed for a specific duration (e.g., maintenance, break)</p>
                                    <div class="flex items-end gap-3">
                                        <div class="flex-1">
                                            <label for="till_time_minutes" class="block text-xs font-medium text-gray-700 mb-1">
                                                Duration (minutes)
                                            </label>
                                            <input type="number"
                                                   name="till_time_minutes"
                                                   id="till_time_minutes"
                                                   min="1"
                                                   placeholder="e.g., 30"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        </div>
                                        <button type="button"
                                                onclick="setTemporaryClosure()"
                                                class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded text-xs font-medium whitespace-nowrap">
                                            Set Closure
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sync to Careem Checkbox -->
                        <div class="mb-6">
                            <label class="flex items-start">
                                <input type="checkbox"
                                       name="sync_to_careem"
                                       value="1"
                                       {{ old('sync_to_careem') ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 mt-0.5">
                                <span class="ml-2">
                                    <span class="text-sm font-medium text-gray-700">Sync changes to Careem</span>
                                    <span class="block text-xs text-gray-500 mt-1">If checked, changes will be synced to Careem API immediately.</span>
                                </span>
                            </label>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('dashboard.careem-branches.index', ['subdomain' => request()->route('subdomain')]) }}"
                               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Update Branch
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Branch Status Info -->
            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Branch Status Information</h3>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">State</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($branch->state === 'MAPPED') bg-green-100 text-green-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ $branch->state }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">POS Integration</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $branch->pos_integration_enabled ? 'Enabled' : 'Disabled' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Visibility Status</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $branch->visibility_status_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Synced</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($branch->synced_at)
                                    {{ $branch->synced_at->format('M d, Y H:i') }} ({{ $branch->synced_at->diffForHumans() }})
                                @else
                                    <span class="text-yellow-600">Never</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function setTemporaryClosure() {
            const minutes = document.getElementById('till_time_minutes').value;
            if (!minutes || minutes < 1) {
                alert('Please enter a valid duration in minutes');
                return;
            }

            if (!confirm(`Set branch as temporarily closed for ${minutes} minutes?`)) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('dashboard.careem-branches.temporary-status', ['subdomain' => request()->route('subdomain'), 'careemBranch' => $branch->id]) }}";

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status_id';
            statusInput.value = '2';
            form.appendChild(statusInput);

            const minutesInput = document.createElement('input');
            minutesInput.type = 'hidden';
            minutesInput.name = 'till_time_minutes';
            minutesInput.value = minutes;
            form.appendChild(minutesInput);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
    @endpush
</x-app-layout>
