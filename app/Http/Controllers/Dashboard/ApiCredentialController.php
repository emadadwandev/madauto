<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ApiCredential;
use App\Services\LoyverseApiService;
use App\Services\CareemApiService;
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
        $credentials = ApiCredential::orderBy('service')->get()->keyBy('service');

        return view('dashboard.api-credentials.index', compact('credentials'));
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
            'restaurant_id' => 'nullable|string',
            'api_url' => 'nullable|url',
        ]);

        ApiCredential::updateOrCreate(
            [
                'service' => 'careem_catalog',
            ],
            [
                'credentials' => [
                    'client_id' => $validated['client_id'],
                    'client_secret' => $validated['client_secret'],
                    'restaurant_id' => $validated['restaurant_id'] ?? null,
                    'api_url' => $validated['api_url'] ?? config('platforms.careem.api_url'),
                ],
                'is_active' => true,
            ]
        );

        return back()->with('success', 'Careem Catalog API credentials saved successfully!');
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

        ApiCredential::updateOrCreate(
            [
                'service' => 'talabat',
            ],
            [
                'credentials' => [
                    'client_id' => $validated['client_id'],
                    'client_secret' => $validated['client_secret'],
                    'chain_code' => $validated['chain_code'],
                    'vendor_id' => $validated['vendor_id'] ?? null,
                    'api_url' => $validated['api_url'] ?? config('platforms.talabat.api_url'),
                ],
                'is_active' => true,
            ]
        );

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
}
