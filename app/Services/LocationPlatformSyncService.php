<?php

namespace App\Services;

use App\Models\Location;
use App\Exceptions\PlatformApiException;
use Illuminate\Support\Facades\Log;

/**
 * Location Platform Sync Service
 * Handles synchronization of location data (status, hours) with delivery platforms
 */
class LocationPlatformSyncService
{
    /**
     * Sync location status to Careem
     *
     * @param Location $location
     * @param array $options Options: is_active, is_busy
     * @return array Response with success status
     */
    public function syncCareemStatus(Location $location, array $options = []): array
    {
        if (!$location->supportsPlatform('careem')) {
            return [
                'success' => false,
                'message' => 'Location is not configured for Careem platform.',
            ];
        }

        if (!$location->careem_store_id) {
            return [
                'success' => false,
                'message' => 'Careem Store ID not configured for this location.',
            ];
        }

        try {
            $careemService = new CareemApiService($location->tenant_id);

            // Determine status
            $isActive = $options['is_active'] ?? $location->is_active;
            $isBusy = $options['is_busy'] ?? $location->is_busy;

            // Call Careem Store API to update status
            $result = $careemService->updateStoreStatus(
                $location->careem_store_id,
                $isActive,
                $isBusy
            );

            // Update sync status
            $this->updateSyncStatus($location, 'careem', [
                'last_sync' => now()->toIso8601String(),
                'status' => 'success',
                'is_active' => $isActive,
                'is_busy' => $isBusy,
            ]);

            Log::info('Careem location status synced successfully', [
                'location_id' => $location->id,
                'careem_store_id' => $location->careem_store_id,
                'is_active' => $isActive,
                'is_busy' => $isBusy,
            ]);

            return [
                'success' => true,
                'message' => 'Careem store status updated successfully.',
                'data' => $result,
            ];

        } catch (PlatformApiException $e) {
            $this->updateSyncStatus($location, 'careem', [
                'last_sync' => now()->toIso8601String(),
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);

            Log::error('Failed to sync Careem location status', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update Careem store status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync location operating hours to Careem
     *
     * @param Location $location
     * @return array Response with success status
     */
    public function syncCareemHours(Location $location): array
    {
        if (!$location->supportsPlatform('careem')) {
            return [
                'success' => false,
                'message' => 'Location is not configured for Careem platform.',
            ];
        }

        if (!$location->careem_store_id) {
            return [
                'success' => false,
                'message' => 'Careem Store ID not configured for this location.',
            ];
        }

        try {
            $careemService = new CareemApiService($location->tenant_id);

            // Transform opening hours to Careem format
            $hours = $this->transformHoursToCareem($location->opening_hours ?? []);

            // Call Careem Store API to update operating hours
            $result = $careemService->updateStoreHours(
                $location->careem_store_id,
                $hours
            );

            // Update sync status
            $this->updateSyncStatus($location, 'careem', [
                'last_hours_sync' => now()->toIso8601String(),
                'hours_status' => 'success',
            ]);

            Log::info('Careem location hours synced successfully', [
                'location_id' => $location->id,
                'careem_store_id' => $location->careem_store_id,
            ]);

            return [
                'success' => true,
                'message' => 'Careem store hours updated successfully.',
                'data' => $result,
            ];

        } catch (PlatformApiException $e) {
            $this->updateSyncStatus($location, 'careem', [
                'last_hours_sync' => now()->toIso8601String(),
                'hours_status' => 'error',
                'hours_error' => $e->getMessage(),
            ]);

            Log::error('Failed to sync Careem location hours', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update Careem store hours: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync location status to Talabat
     *
     * @param Location $location
     * @param array $options Options: is_active, is_busy
     * @return array Response with success status
     */
    public function syncTalabatStatus(Location $location, array $options = []): array
    {
        if (!$location->supportsPlatform('talabat')) {
            return [
                'success' => false,
                'message' => 'Location is not configured for Talabat platform.',
            ];
        }

        if (!$location->talabat_vendor_id) {
            return [
                'success' => false,
                'message' => 'Talabat Vendor ID not configured for this location.',
            ];
        }

        try {
            $talabatService = new TalabatApiService($location->tenant_id);

            // Determine status
            $isActive = $options['is_active'] ?? $location->is_active;
            $isBusy = $options['is_busy'] ?? $location->is_busy;

            // Talabat vendor status: ONLINE, OFFLINE, BUSY
            $vendorStatus = $this->determineTalabatStatus($isActive, $isBusy);

            // Call Talabat POS availability API
            $result = $talabatService->updateVendorStatus(
                $location->talabat_vendor_id,
                $vendorStatus
            );

            // Update sync status
            $this->updateSyncStatus($location, 'talabat', [
                'last_sync' => now()->toIso8601String(),
                'status' => 'success',
                'vendor_status' => $vendorStatus,
            ]);

            Log::info('Talabat vendor status synced successfully', [
                'location_id' => $location->id,
                'talabat_vendor_id' => $location->talabat_vendor_id,
                'vendor_status' => $vendorStatus,
            ]);

            return [
                'success' => true,
                'message' => 'Talabat vendor status updated successfully.',
                'data' => $result,
            ];

        } catch (PlatformApiException $e) {
            $this->updateSyncStatus($location, 'talabat', [
                'last_sync' => now()->toIso8601String(),
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);

            Log::error('Failed to sync Talabat vendor status', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update Talabat vendor status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync location status to all configured platforms
     *
     * @param Location $location
     * @param array $options Options: is_active, is_busy
     * @return array Results per platform
     */
    public function syncAllPlatforms(Location $location, array $options = []): array
    {
        $results = [];

        if ($location->supportsPlatform('careem')) {
            $results['careem'] = $this->syncCareemStatus($location, $options);
        }

        if ($location->supportsPlatform('talabat')) {
            $results['talabat'] = $this->syncTalabatStatus($location, $options);
        }

        return $results;
    }

    /**
     * Transform opening hours to Careem format
     *
     * @param array $hours Hours in our format: {monday: {open: "09:00", close: "22:00"}}
     * @return array Hours in Careem format
     */
    protected function transformHoursToCareem(array $hours): array
    {
        $careemHours = [];

        $dayMap = [
            'monday' => 'MONDAY',
            'tuesday' => 'TUESDAY',
            'wednesday' => 'WEDNESDAY',
            'thursday' => 'THURSDAY',
            'friday' => 'FRIDAY',
            'saturday' => 'SATURDAY',
            'sunday' => 'SUNDAY',
        ];

        foreach ($hours as $day => $times) {
            if (isset($times['open']) && isset($times['close'])) {
                $careemHours[] = [
                    'day' => $dayMap[$day] ?? strtoupper($day),
                    'open_time' => $times['open'],
                    'close_time' => $times['close'],
                    'is_open' => true,
                ];
            }
        }

        return $careemHours;
    }

    /**
     * Determine Talabat vendor status based on location state
     *
     * @param bool $isActive
     * @param bool $isBusy
     * @return string ONLINE, OFFLINE, or BUSY
     */
    protected function determineTalabatStatus(bool $isActive, bool $isBusy): string
    {
        if (!$isActive) {
            return 'OFFLINE';
        }

        if ($isBusy) {
            return 'BUSY';
        }

        return 'ONLINE';
    }

    /**
     * Update sync status for a platform in location metadata
     *
     * @param Location $location
     * @param string $platform careem or talabat
     * @param array $status Status data to merge
     * @return void
     */
    protected function updateSyncStatus(Location $location, string $platform, array $status): void
    {
        $syncStatus = $location->platform_sync_status ?? [];
        $syncStatus[$platform] = array_merge($syncStatus[$platform] ?? [], $status);

        $location->platform_sync_status = $syncStatus;
        $location->save();
    }
}
