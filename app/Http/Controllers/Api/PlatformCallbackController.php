<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handle callbacks from delivery platforms (Careem, Talabat)
 * for async catalog validation and sync status updates
 */
class PlatformCallbackController extends Controller
{
    /**
     * Handle Talabat (Delivery Hero) catalog validation callback
     *
     * POST /api/callbacks/talabat
     *
     * Expected payload from Talabat:
     * {
     *   "importId": "uuid",
     *   "status": "in_progress" | "successful" | "failed",
     *   "validationErrors": [...]  // If failed
     * }
     */
    public function talabat(Request $request)
    {
        Log::info('Talabat callback received', [
            'payload' => $request->all(),
            'ip' => $request->ip(),
        ]);

        try {
            $importId = $request->input('importId');
            $status = $request->input('status');
            $errors = $request->input('validationErrors', []);

            // Find menu by import ID in platform_menu_id column
            $menuPlatform = DB::table('menu_platform')
                ->where('platform', 'talabat')
                ->where('platform_menu_id', 'like', "%{$importId}%")
                ->first();

            if (!$menuPlatform) {
                Log::warning('Talabat callback for unknown import ID', [
                    'import_id' => $importId,
                ]);

                return response()->json([
                    'status' => 'received',
                    'message' => 'Import ID not found',
                ], 404);
            }

            // Map Talabat status to our sync_status
            $syncStatus = match ($status) {
                'successful' => 'synced',
                'failed' => 'failed',
                'in_progress' => 'syncing',
                default => 'pending',
            };

            // Prepare error message if failed
            $errorMessage = null;
            if ($status === 'failed' && !empty($errors)) {
                $errorMessage = 'Validation errors: ' . json_encode($errors);
            }

            // Update menu platform status
            DB::table('menu_platform')
                ->where('id', $menuPlatform->id)
                ->update([
                    'sync_status' => $syncStatus,
                    'sync_error' => $errorMessage,
                    'last_synced_at' => $status === 'successful' ? now() : null,
                    'updated_at' => now(),
                ]);

            Log::info('Talabat callback processed successfully', [
                'import_id' => $importId,
                'status' => $status,
                'sync_status' => $syncStatus,
                'menu_id' => $menuPlatform->menu_id,
            ]);

            return response()->json([
                'status' => 'processed',
                'message' => 'Callback received and processed',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to process Talabat callback', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process callback',
            ], 500);
        }
    }

    /**
     * Handle Careem catalog sync callback
     *
     * POST /api/callbacks/careem
     */
    public function careem(Request $request)
    {
        Log::info('Careem callback received', [
            'payload' => $request->all(),
            'ip' => $request->ip(),
        ]);

        try {
            // Careem callback structure (adjust based on actual API)
            $catalogId = $request->input('catalog_id') ?? $request->input('id');
            $status = $request->input('status');
            $errors = $request->input('errors', []);

            if (!$catalogId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Missing catalog_id',
                ], 400);
            }

            // Find menu by catalog ID
            $menuPlatform = DB::table('menu_platform')
                ->where('platform', 'careem')
                ->where('platform_menu_id', 'like', "%{$catalogId}%")
                ->first();

            if (!$menuPlatform) {
                Log::warning('Careem callback for unknown catalog ID', [
                    'catalog_id' => $catalogId,
                ]);

                return response()->json([
                    'status' => 'received',
                    'message' => 'Catalog ID not found',
                ], 404);
            }

            // Map status
            $syncStatus = match (strtolower($status)) {
                'active', 'published', 'success' => 'synced',
                'failed', 'rejected' => 'failed',
                'processing', 'pending' => 'syncing',
                default => 'pending',
            };

            $errorMessage = !empty($errors) ? json_encode($errors) : null;

            // Update status
            DB::table('menu_platform')
                ->where('id', $menuPlatform->id)
                ->update([
                    'sync_status' => $syncStatus,
                    'sync_error' => $errorMessage,
                    'last_synced_at' => $syncStatus === 'synced' ? now() : null,
                    'updated_at' => now(),
                ]);

            Log::info('Careem callback processed successfully', [
                'catalog_id' => $catalogId,
                'status' => $status,
                'sync_status' => $syncStatus,
                'menu_id' => $menuPlatform->menu_id,
            ]);

            return response()->json([
                'status' => 'processed',
                'message' => 'Callback received and processed',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to process Careem callback', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process callback',
            ], 500);
        }
    }
}
