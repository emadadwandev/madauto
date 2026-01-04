<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ApiCredential;
use App\Services\CareemApiService;
use App\Services\LoyverseApiService;
use App\Services\TalabatApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiCredentialController extends Controller
{
    protected $loyverseApiService;

    public function __construct(LoyverseApiService $loyverseApiService)
    {
        $this->loyverseApiService = $loyverseApiService;
    }

    /**
     * Display API credentials management page
     */
    public function index(string $subdomain)
    {
        // Fetch all credentials for the current tenant
        $allCredentials = ApiCredential::get();

        // Group by service and map to key-value pairs for the form inputs
        $credentials = $allCredentials->groupBy('service')->map(function ($items) {
            return $items->mapWithKeys(function ($item) {
                return [$item->credential_type => $item->credential_value];
            });
        });

        // Pass the raw collection for the "Saved Credentials" table
        $rawCredentials = $allCredentials;

        return view('dashboard.api-credentials.index', compact('credentials', 'rawCredentials'));
    }

    /**
     * Update or create simple credential (Loyverse, Careem webhook)
     */
    public function store(Request $request, string $subdomain)
    {
        $validated = $request->validate([
            'service' => 'required|string|in:careem,loyverse,talabat',
            'credential_type' => 'required|string',
            'credential_value' => 'required|string',
        ]);

        $credential = ApiCredential::updateOrCreate(
            [
                'service' => $validated['service'],
                'credential_type' => $validated['credential_type'],
            ],
            [
                'credential_value' => $validated['credential_value'],
                'is_active' => true,
            ]
        );

        return back()->with('success', 'API credential saved successfully');
    }

    /**
     * Store or update Careem Catalog API credentials
     */
    public function storeCareemCatalog(Request $request, string $subdomain)
    {
        $validated = $request->validate([
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'client_name' => 'nullable|string',
            'user_agent' => 'nullable|string',
            'api_url' => 'nullable|url',
            'token_url' => 'nullable|url',
        ]);

        // Save Client ID
        ApiCredential::updateOrCreate(
            [
                'service' => 'careem_catalog',
                'credential_type' => 'client_id',
            ],
            [
                'credential_value' => $validated['client_id'],
                'is_active' => true,
            ]
        );

        // Save Client Secret
        ApiCredential::updateOrCreate(
            [
                'service' => 'careem_catalog',
                'credential_type' => 'client_secret',
            ],
            [
                'credential_value' => $validated['client_secret'],
                'is_active' => true,
            ]
        );

        // Save Client Name (Optional)
        if (! empty($validated['client_name'])) {
            ApiCredential::updateOrCreate(
                [
                    'service' => 'careem_catalog',
                    'credential_type' => 'client_name',
                ],
                [
                    'credential_value' => $validated['client_name'],
                    'is_active' => true,
                ]
            );
        }

        // Save User Agent (Optional)
        if (! empty($validated['user_agent'])) {
            ApiCredential::updateOrCreate(
                [
                    'service' => 'careem_catalog',
                    'credential_type' => 'user_agent',
                ],
                [
                    'credential_value' => $validated['user_agent'],
                    'is_active' => true,
                ]
            );
        }

        // Save API URL (Optional)
        if (! empty($validated['api_url'])) {
            ApiCredential::updateOrCreate(
                [
                    'service' => 'careem_catalog',
                    'credential_type' => 'api_url',
                ],
                [
                    'credential_value' => $validated['api_url'],
                    'is_active' => true,
                ]
            );
        }

        // Save Token URL (Optional)
        if (! empty($validated['token_url'])) {
            ApiCredential::updateOrCreate(
                [
                    'service' => 'careem_catalog',
                    'credential_type' => 'token_url',
                ],
                [
                    'credential_value' => $validated['token_url'],
                    'is_active' => true,
                ]
            );
        }

        return back()->with('success', 'Careem API credentials saved successfully!');
    }

    /**
     * Store or update Talabat Catalog API credentials
     */
    public function storeTalabatCatalog(Request $request, string $subdomain)
    {
        $validated = $request->validate([
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'chain_code' => 'required|string',
            'vendor_id' => 'nullable|string',
            'api_url' => 'nullable|url',
        ]);

        // Save Client ID
        ApiCredential::updateOrCreate(
            [
                'service' => 'talabat',
                'credential_type' => 'client_id',
            ],
            [
                'credential_value' => $validated['client_id'],
                'is_active' => true,
            ]
        );

        // Save Client Secret
        ApiCredential::updateOrCreate(
            [
                'service' => 'talabat',
                'credential_type' => 'client_secret',
            ],
            [
                'credential_value' => $validated['client_secret'],
                'is_active' => true,
            ]
        );

        // Save Chain Code
        ApiCredential::updateOrCreate(
            [
                'service' => 'talabat',
                'credential_type' => 'chain_code',
            ],
            [
                'credential_value' => $validated['chain_code'],
                'is_active' => true,
            ]
        );

        // Save Vendor ID (Optional)
        if (! empty($validated['vendor_id'])) {
            ApiCredential::updateOrCreate(
                [
                    'service' => 'talabat',
                    'credential_type' => 'vendor_id',
                ],
                [
                    'credential_value' => $validated['vendor_id'],
                    'is_active' => true,
                ]
            );
        }

        // Save API URL (Optional)
        if (! empty($validated['api_url'])) {
            ApiCredential::updateOrCreate(
                [
                    'service' => 'talabat',
                    'credential_type' => 'api_url',
                ],
                [
                    'credential_value' => $validated['api_url'],
                    'is_active' => true,
                ]
            );
        }

        return back()->with('success', 'Talabat Catalog API credentials saved successfully!');
    }

    /**
     * Toggle credential status
     */
    public function toggle(string $subdomain, ApiCredential $apiCredential)
    {
        $apiCredential->update(['is_active' => ! $apiCredential->is_active]);

        return back()->with('success', 'Credential status updated');
    }

    /**
     * Delete credential
     */
    public function destroy(string $subdomain, ApiCredential $apiCredential)
    {
        $apiCredential->delete();

        return back()->with('success', 'Credential deleted successfully');
    }

    /**
     * Test Loyverse API connection
     */
    public function testConnection(string $subdomain)
    {
        try {
            $result = $this->loyverseApiService->testConnection();

            if ($result) {
                return back()->with('success', 'Loyverse API connection successful!');
            } else {
                return back()->with('error', 'Loyverse API connection failed');
            }
        } catch (\Exception $e) {
            Log::error('Loyverse API test failed: '.$e->getMessage());

            return back()->with('error', 'Connection test failed: '.$e->getMessage());
        }
    }

    /**
     * Test Careem Catalog API connection
     */
    public function testCareemConnection(string $subdomain)
    {
        try {
            $tenantId = tenant()->id;
            $careemService = new CareemApiService($tenantId);

            if ($careemService->testConnection()) {
                return back()->with('success', 'Careem Catalog API connection successful! ✅');
            } else {
                return back()->with('error', 'Careem Catalog API connection failed. Please check your credentials.');
            }
        } catch (\Exception $e) {
            Log::error('Careem API test failed', [
                'error' => $e->getMessage(),
                'tenant_id' => tenant()->id,
            ]);

            return back()->with('error', 'Careem connection test failed: '.$e->getMessage());
        }
    }

    /**
     * Test Talabat Catalog API connection
     */
    public function testTalabatConnection(string $subdomain)
    {
        try {
            $tenantId = tenant()->id;
            $talabatService = new TalabatApiService($tenantId);

            if ($talabatService->testConnection()) {
                return back()->with('success', 'Talabat Catalog API connection successful! ✅');
            } else {
                return back()->with('error', 'Talabat Catalog API connection failed. Please check your credentials.');
            }
        } catch (\Exception $e) {
            Log::error('Talabat API test failed', [
                'error' => $e->getMessage(),
                'tenant_id' => tenant()->id,
            ]);

            return back()->with('error', 'Talabat connection test failed: '.$e->getMessage());
        }
    }

    /**
     * Fetch stores from Loyverse API
     */
    public function fetchStores(string $subdomain)
    {
        try {
            $stores = $this->loyverseApiService->getStores();

            if (empty($stores)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No stores found in your Loyverse account',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'stores' => $stores,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch Loyverse stores', [
                'error' => $e->getMessage(),
                'tenant_id' => tenant()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stores: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set selected Loyverse store for order syncing
     */
    public function setStore(Request $request, string $subdomain)
    {
        $validated = $request->validate([
            'store_id' => 'required|string',
        ]);

        try {
            // Verify the store exists in Loyverse
            $store = $this->loyverseApiService->getStore($validated['store_id']);

            if (! $store) {
                return back()->with('error', 'Selected store not found in Loyverse');
            }

            // Update tenant with selected store ID
            tenant()->update([
                'loyverse_store_id' => $validated['store_id'],
            ]);

            return back()->with('success', 'Loyverse store selected successfully: '.$store['name']);
        } catch (\Exception $e) {
            Log::error('Failed to set Loyverse store', [
                'error' => $e->getMessage(),
                'tenant_id' => tenant()->id,
                'store_id' => $validated['store_id'],
            ]);

            return back()->with('error', 'Failed to set store: '.$e->getMessage());
        }
    }
}
