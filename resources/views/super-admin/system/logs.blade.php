<x-super-admin-layout>
    <div class="space-y-6">
        <!-- Page Header -->
        <div>
            <div class="flex items-center">
                <a href="{{ route('super-admin.system.index') }}" class="mr-3 text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-2xl font-semibold text-gray-900">Application Logs</h1>
            </div>
            <p class="mt-1 text-sm text-gray-500">View recent application log entries (last 200 lines)</p>
        </div>

        <!-- Filter -->
        <div class="bg-white shadow rounded-lg p-4">
            <form method="GET" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label for="level" class="block text-sm font-medium text-gray-700">Log Level</label>
                    <select name="level" id="level"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">All Levels</option>
                        <option value="debug" {{ request('level') === 'debug' ? 'selected' : '' }}>Debug</option>
                        <option value="info" {{ request('level') === 'info' ? 'selected' : '' }}>Info</option>
                        <option value="warning" {{ request('level') === 'warning' ? 'selected' : '' }}>Warning</option>
                        <option value="error" {{ request('level') === 'error' ? 'selected' : '' }}>Error</option>
                        <option value="critical" {{ request('level') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Filter
                </button>
                <a href="{{ route('super-admin.system.logs') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Reset
                </a>
            </form>
        </div>

        <!-- Logs Display -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                @if(count($logs) > 0)
                <div class="bg-gray-900 rounded-md p-4 overflow-x-auto">
                    <pre class="text-green-400 font-mono text-xs leading-relaxed">@foreach($logs as $line){{ $line }}
@endforeach</pre>
                </div>
                <div class="mt-4 text-sm text-gray-500">
                    Showing {{ count($logs) }} most recent log entries
                    @if(request('level'))
                        filtered by level: <strong>{{ strtoupper(request('level')) }}</strong>
                    @endif
                </div>
                @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No logs found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if(request('level'))
                            No {{ request('level') }} level logs found in the log file.
                        @else
                            The log file is empty or doesn't exist yet.
                        @endif
                    </p>
                </div>
                @endif
            </div>
        </div>

        <!-- Log Legend -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-3">Log Level Guide</h3>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-5">
                    <div>
                        <dt class="text-xs font-medium text-gray-500">DEBUG</dt>
                        <dd class="mt-1 text-xs text-gray-600">Detailed debug information</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">INFO</dt>
                        <dd class="mt-1 text-xs text-gray-600">Informational messages</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">WARNING</dt>
                        <dd class="mt-1 text-xs text-gray-600">Warning messages</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">ERROR</dt>
                        <dd class="mt-1 text-xs text-gray-600">Runtime errors</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-gray-500">CRITICAL</dt>
                        <dd class="mt-1 text-xs text-gray-600">Critical conditions</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-sm font-medium text-gray-900 mb-3">Log Management</h3>
                <div class="flex gap-3">
                    <button type="button"
                            onclick="window.location.reload()"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                    <a href="{{ storage_path('logs/laravel.log') }}"
                       class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md shadow-sm text-sm font-medium text-blue-700 bg-white hover:bg-blue-50"
                       title="Log file location">
                        <svg class="h-5 w-5 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        View Full Log File
                    </a>
                </div>
                <p class="mt-3 text-xs text-gray-500">
                    Log file location: <code class="bg-gray-100 px-1 py-0.5 rounded">storage/logs/laravel.log</code>
                </p>
            </div>
        </div>
    </div>
</x-super-admin-layout>
