<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class SystemController extends Controller
{
    /**
     * Display system health dashboard.
     */
    public function index()
    {
        $healthChecks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'api' => $this->checkApiHealth(),
        ];

        // Get system statistics
        $stats = [
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'recent_errors' => $this->getRecentErrors(),
            'queue_size' => $this->getQueueSize(),
            'sync_failures_24h' => SyncLog::where('status', 'failed')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'webhook_failures_24h' => WebhookLog::where('status', 'failed')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
        ];

        return view('super-admin.system.index', compact('healthChecks', 'stats'));
    }

    /**
     * Display failed jobs.
     */
    public function failedJobs(Request $request)
    {
        $query = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('queue', 'like', "%{$search}%")
                    ->orWhere('exception', 'like', "%{$search}%");
            });
        }

        $failedJobs = $query->paginate(20)->withQueryString();

        return view('super-admin.system.failed-jobs', compact('failedJobs'));
    }

    /**
     * Retry a failed job.
     */
    public function retryJob(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        try {
            Artisan::call('queue:retry', ['id' => [$request->id]]);

            return redirect()
                ->back()
                ->with('success', 'Job queued for retry.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to retry job: '.$e->getMessage());
        }
    }

    /**
     * Delete a failed job.
     */
    public function deleteJob(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        DB::table('failed_jobs')->where('id', $request->id)->delete();

        return redirect()
            ->back()
            ->with('success', 'Failed job deleted.');
    }

    /**
     * Display sync logs.
     */
    public function syncLogs(Request $request)
    {
        $query = SyncLog::with('tenant')->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $syncLogs = $query->paginate(50)->withQueryString();

        return view('super-admin.system.sync-logs', compact('syncLogs'));
    }

    /**
     * Display webhook logs.
     */
    public function webhookLogs(Request $request)
    {
        $query = WebhookLog::orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $webhookLogs = $query->paginate(50)->withQueryString();

        return view('super-admin.system.webhook-logs', compact('webhookLogs'));
    }

    /**
     * Display application logs.
     */
    public function applicationLogs(Request $request)
    {
        $logFile = storage_path('logs/laravel.log');
        $logs = [];

        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            $lines = array_reverse(explode("\n", $content));

            // Get last 200 lines
            $logs = array_slice($lines, 0, 200);

            // Filter by level if specified
            if ($request->filled('level')) {
                $level = strtoupper($request->level);
                $logs = array_filter($logs, function ($line) use ($level) {
                    return str_contains($line, ".{$level}:");
                });
            }
        }

        return view('super-admin.system.logs', compact('logs'));
    }

    /**
     * Clear application cache.
     */
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');

            return redirect()
                ->back()
                ->with('success', 'All caches cleared successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to clear cache: '.$e->getMessage());
        }
    }

    /**
     * Run queue worker status check.
     */
    public function queueStatus()
    {
        // This would need to be implemented based on your queue monitoring solution
        // For example, using Laravel Horizon if available

        $status = [
            'workers_running' => false, // TODO: Implement worker check
            'pending_jobs' => $this->getQueueSize(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ];

        return response()->json($status);
    }

    // Helper methods

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'health_check_'.time();
            Cache::put($key, 'test', 10);
            $result = Cache::get($key);
            Cache::forget($key);

            if ($result === 'test') {
                return ['status' => 'healthy', 'message' => 'Cache working'];
            }

            return ['status' => 'warning', 'message' => 'Cache not storing values'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            $size = $this->getQueueSize();
            $failed = DB::table('failed_jobs')->count();

            if ($failed > 100) {
                return ['status' => 'warning', 'message' => "High failed job count: {$failed}"];
            }

            return ['status' => 'healthy', 'message' => "Queue size: {$size}, Failed: {$failed}"];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        try {
            $path = storage_path('app');
            $freeSpace = disk_free_space($path);
            $totalSpace = disk_total_space($path);
            $usedPercent = (($totalSpace - $freeSpace) / $totalSpace) * 100;

            if ($usedPercent > 90) {
                return ['status' => 'warning', 'message' => sprintf('Disk %.1f%% full', $usedPercent)];
            }

            return ['status' => 'healthy', 'message' => sprintf('Disk %.1f%% used', $usedPercent)];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkApiHealth(): array
    {
        // Check recent API sync failures
        $recentFailures = SyncLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentFailures > 10) {
            return ['status' => 'warning', 'message' => "{$recentFailures} sync failures in last hour"];
        }

        return ['status' => 'healthy', 'message' => 'API syncs operating normally'];
    }

    private function getRecentErrors(): int
    {
        $logFile = storage_path('logs/laravel.log');

        if (! file_exists($logFile)) {
            return 0;
        }

        $content = file_get_contents($logFile);
        $lines = explode("\n", $content);

        $errors = 0;
        $yesterday = now()->subDay();

        foreach (array_reverse($lines) as $line) {
            if (str_contains($line, '.ERROR:') && preg_match('/\[(.*?)\]/', $line, $matches)) {
                try {
                    $logDate = \Carbon\Carbon::parse($matches[1]);
                    if ($logDate->gt($yesterday)) {
                        $errors++;
                    } else {
                        break; // Stop if we've gone past yesterday
                    }
                } catch (\Exception $e) {
                    // Skip malformed dates
                }
            }
        }

        return $errors;
    }

    private function getQueueSize(): int
    {
        try {
            // For database queue driver
            return DB::table('jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}
