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

        // Update sync status to 'syncing'
        $this->updatePlatformStatus('syncing', null, null);

        try {
            // Reload menu with all necessary relationships
            $menu = Menu::with(['items.modifierGroups.modifiers', 'locations'])
                ->findOrFail($this->menu->id);

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
            $this->handleFailure($e);

            // Re-throw for retry logic if retryable
            if ($e->isRetryable() && $this->attempts() < $this->tries) {
                throw $e;
            }
        } catch (\Exception $e) {
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
        $transformer = new CareemMenuTransformer();

        // Transform menu to Careem format
        $catalogData = $transformer->transform($menu);

        // Get restaurant ID from tenant credentials if available
        $credential = \App\Models\ApiCredential::where('tenant_id', $this->tenantId)
            ->where('service', 'careem_catalog')
            ->first();

        $restaurantId = $credential?->credentials['restaurant_id'] ?? null;

        // Submit to Careem
        $result = $apiService->submitCatalog($catalogData, $restaurantId);

        return [
            'success' => $result['success'] ?? false,
            'platform_menu_id' => $result['catalog_id'] ?? null,
            'message' => $result['message'] ?? 'Submitted to Careem',
        ];
    }

    /**
     * Sync menu to Talabat (Delivery Hero) platform
     */
    protected function syncToTalabat(Menu $menu): array
    {
        $apiService = new TalabatApiService($this->tenantId);
        $transformer = new TalabatMenuTransformer();

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
