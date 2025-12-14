<?php

namespace App\Jobs;

use App\Exceptions\PlatformApiException;
use App\Models\Menu;
use App\Services\CareemApiService;
use App\Services\CareemMenuTransformer;
use App\Services\TalabatApiService;
use App\Services\TalabatMenuTransformer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncMenuToPlatformJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Menu $menu;

    public string $platform;

    public int $tenantId;

    /**
     * Job configuration
     */
    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [60, 300, 600];  // 1 min, 5 min, 10 min

    /**
     * Create a new job instance
     */
    public function __construct(Menu $menu, string $platform, int $tenantId)
    {
        $this->menu = $menu->withoutRelations();  // Prevent large serialization
        $this->platform = $platform;
        $this->tenantId = $tenantId;
        $this->onQueue(config('platforms.sync_settings.queue', 'platform-sync'));
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        Log::info('Starting menu sync to platform', [
            'menu_id' => $this->menu->id,
            'menu_name' => $this->menu->name,
            'platform' => $this->platform,
            'tenant_id' => $this->tenantId,
            'attempt' => $this->attempts(),
        ]);

        // Create initial sync log
        $syncLog = \App\Models\MenuSyncLog::create([
            'tenant_id' => $this->tenantId,
            'menu_id' => $this->menu->id,
            'platform' => $this->platform,
            'action' => 'sync_started',
            'status' => 'pending',
            'message' => 'Menu sync started',
            'metadata' => [
                'attempt' => $this->attempts(),
                'queue' => $this->queue,
            ],
        ]);

        // Update sync status to 'syncing'
        $this->updatePlatformStatus('syncing', null, null);

        try {
            // Reload menu with all necessary relationships
            $menu = Menu::with(['items.modifierGroups.modifiers', 'locations'])
                ->findOrFail($this->menu->id);

            // Update log to processing
            $syncLog->update(['status' => 'processing']);

            // Sync based on platform
            $result = match ($this->platform) {
                'careem' => $this->syncToCareem($menu),
                'talabat' => $this->syncToTalabat($menu),
                default => throw new \InvalidArgumentException("Unsupported platform: {$this->platform}"),
            };

            // Update status based on result
            if ($result['success']) {
                $this->updatePlatformStatus(
                    'synced',
                    $result['platform_menu_id'] ?? null,
                    null
                );

                // Update sync log to success
                $syncLog->update([
                    'status' => 'success',
                    'action' => 'sync_completed',
                    'message' => $result['message'] ?? 'Menu synced successfully',
                    'metadata' => array_merge($syncLog->metadata ?? [], [
                        'platform_menu_id' => $result['platform_menu_id'] ?? null,
                        'catalog_id' => $result['catalog_id'] ?? null,
                        'request_id' => $result['request_id'] ?? null,
                        'api_response' => $result['api_response'] ?? null,
                        'status_check_response' => $result['status_response'] ?? null,
                        'result' => $result,
                    ]),
                ]);

                Log::info('Menu synced successfully to platform', [
                    'menu_id' => $this->menu->id,
                    'platform' => $this->platform,
                    'import_id' => $result['platform_menu_id'] ?? null,
                ]);
            } else {
                throw new PlatformApiException(
                    $this->platform,
                    $result['message'] ?? 'Sync failed without specific error'
                );
            }

        } catch (PlatformApiException $e) {
            // Update sync log to failed
            $syncLog->update([
                'status' => 'failed',
                'action' => 'sync_failed',
                'message' => $e->getMessage(),
                'metadata' => array_merge($syncLog->metadata ?? [], [
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'is_retryable' => $e->isRetryable(),
                    'api_response' => $e->getResponse() ?? null,
                    'trace' => $e->getTraceAsString(),
                ]),
            ]);

            $this->handleFailure($e);

            // Re-throw for retry logic if retryable
            if ($e->isRetryable() && $this->attempts() < $this->tries) {
                throw $e;
            }
        } catch (\Exception $e) {
            // Update sync log to failed
            $syncLog->update([
                'status' => 'failed',
                'action' => 'sync_failed',
                'message' => $e->getMessage(),
                'metadata' => array_merge($syncLog->metadata ?? [], [
                    'error' => $e->getMessage(),
                    'error_type' => get_class($e),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]),
            ]);

            $this->handleFailure($e);

            // Re-throw for retry logic
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }

    /**
     * Sync menu to Careem platform
     */
    protected function syncToCareem(Menu $menu): array
    {
        $apiService = new CareemApiService($this->tenantId);
        $transformer = new CareemMenuTransformer;

        // Get brand and branch IDs from menu's associated branch
        $branch = $menu->locations()->first()?->careemBranch;
        $brandId = $branch?->brand?->careem_brand_id;
        $branchId = $branch?->careem_branch_id;
        
        // Generate catalogId from menu and branch (or use existing if stored)
        $catalogId = $menu->careem_catalog_id ?? 'catalog_' . $menu->id . '_' . ($branchId ?? 'default');

        // Save catalogId to menu if it's new
        if (!$menu->careem_catalog_id) {
            $menu->update(['careem_catalog_id' => $catalogId]);
        }

        if (!$brandId || !$branchId) {
            Log::warning('Careem sync attempted without brand/branch mapping', [
                'menu_id' => $menu->id,
                'brand_id' => $brandId,
                'branch_id' => $branchId,
            ]);
        }

        // Transform menu to Careem format with catalogId
        $catalogData = $transformer->transform($menu, $catalogId);

        // Log the request payload
        Log::info('Submitting catalog to Careem', [
            'menu_id' => $menu->id,
            'catalog_id' => $catalogId,
            'brand_id' => $brandId,
            'branch_id' => $branchId,
            'payload_size' => strlen(json_encode($catalogData)),
            'items_count' => count($catalogData['items'] ?? []),
            'categories_count' => count($catalogData['categories'] ?? []),
        ]);

        // Submit to Careem
        $result = $apiService->submitCatalog($catalogData, $brandId, $branchId, $catalogId);

        // Log the API response
        Log::info('Careem API response received', [
            'menu_id' => $menu->id,
            'catalog_id' => $catalogId,
            'result' => $result,
        ]);

        // Store request_id for status checking
        $requestId = $result['data']['request_id'] ?? $result['catalog_id'] ?? null;

        if ($requestId) {
            // Check catalog status (Careem processes async)
            try {
                $statusResult = $apiService->getCatalogStatus($requestId);

                // Status can be: pending, processing, accepted, rejected
                $status = $statusResult['status'] ?? 'unknown';

                Log::info('Careem catalog status checked', [
                    'request_id' => $requestId,
                    'status' => $status,
                    'result' => $statusResult,
                ]);

                // If still processing, mark as pending (will be checked by a separate status job)
                if (in_array($status, ['pending', 'processing'])) {
                    return [
                        'success' => false,
                        'platform_menu_id' => $requestId,
                        'message' => 'Catalog submitted, processing by Careem (Status: ' . $status . ')',
                        'status' => 'processing',
                        'api_response' => $result,
                        'status_response' => $statusResult,
                        'catalog_id' => $catalogId,
                        'request_id' => $requestId,
                    ];
                }

                // If accepted, mark as success
                if ($status === 'accepted') {
                    return [
                        'success' => true,
                        'platform_menu_id' => $requestId,
                        'message' => 'Catalog accepted by Careem',
                        'api_response' => $result,
                        'status_response' => $statusResult,
                        'catalog_id' => $catalogId,
                        'request_id' => $requestId,
                    ];
                }

                // If rejected, mark as failed
                if ($status === 'rejected') {
                    $errorMessage = 'Catalog rejected by Careem: ' . ($statusResult['message'] ?? 'Unknown reason');
                    Log::error('Careem catalog rejected', [
                        'request_id' => $requestId,
                        'status_result' => $statusResult,
                        'submit_result' => $result,
                    ]);
                    throw new \Exception($errorMessage);
                }

            } catch (\Exception $e) {
                Log::warning('Could not check catalog status immediately', [
                    'request_id' => $requestId,
                    'error' => $e->getMessage(),
                ]);

                // Return as processing, status will be checked later
                return [
                    'success' => false,
                    'platform_menu_id' => $requestId,
                    'message' => 'Catalog submitted, status check pending',
                    'status' => 'processing',
                ];
            }
        }

        return [
            'success' => $result['success'] ?? false,
            'platform_menu_id' => $requestId,
            'message' => $result['message'] ?? 'Submitted to Careem',
        ];
    }

    /**
     * Sync menu to Talabat (Delivery Hero) platform
     */
    protected function syncToTalabat(Menu $menu): array
    {
        $apiService = new TalabatApiService($this->tenantId);
        $transformer = new TalabatMenuTransformer;

        // Transform menu to Talabat format
        $catalogData = $transformer->transform($menu);

        // Get vendor ID from tenant credentials if available
        $credential = \App\Models\ApiCredential::where('tenant_id', $this->tenantId)
            ->where('service', 'talabat')
            ->first();

        $vendorId = $credential?->credentials['vendor_id'] ?? null;
        $callbackUrl = config('platforms.talabat.callback_url');

        // Submit to Talabat
        $result = $apiService->submitCatalog($catalogData, $vendorId, $callbackUrl);

        return [
            'success' => $result['success'] ?? false,
            'platform_menu_id' => $result['import_id'] ?? null,
            'message' => $result['message'] ?? 'Submitted to Talabat',
        ];
    }

    /**
     * Update platform sync status in database
     */
    protected function updatePlatformStatus(
        string $status,
        ?string $platformMenuId = null,
        ?string $error = null
    ): void {
        DB::table('menu_platform')
            ->where('menu_id', $this->menu->id)
            ->where('platform', $this->platform)
            ->update([
                'sync_status' => $status,
                'last_synced_at' => $status === 'synced' ? now() : null,
                'published_at' => $status === 'synced' ? now() : null,
                'platform_menu_id' => $platformMenuId ? json_encode(['id' => $platformMenuId]) : null,
                'sync_error' => $error,
                'updated_at' => now(),
            ]);
    }

    /**
     * Handle job failure
     */
    protected function handleFailure(\Exception $exception): void
    {
        $errorMessage = $exception->getMessage();

        if ($exception instanceof PlatformApiException) {
            $errorMessage = "[{$exception->getPlatform()}] {$exception->getMessage()} (Status: {$exception->getStatusCode()})";
        }

        Log::error('Menu sync failed', [
            'menu_id' => $this->menu->id,
            'platform' => $this->platform,
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
            'error' => $errorMessage,
            'exception_class' => get_class($exception),
        ]);

        // Update status to failed if this is the last attempt
        if ($this->attempts() >= $this->tries) {
            $this->updatePlatformStatus('failed', null, $errorMessage);
        }
    }

    /**
     * Handle job failure after all retries exhausted
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Menu sync permanently failed after all retries', [
            'menu_id' => $this->menu->id,
            'platform' => $this->platform,
            'tenant_id' => $this->tenantId,
            'error' => $exception->getMessage(),
        ]);

        $this->updatePlatformStatus('failed', null, $exception->getMessage());
    }

    /**
     * Determine if job should be retried after exception
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(6);  // Stop retrying after 6 hours
    }
}
