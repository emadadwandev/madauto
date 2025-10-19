<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ApiCredential;
use App\Services\LoyverseApiService;
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
    public function index()
    {
        $credentials = ApiCredential::orderBy('service')->get();

        return view('dashboard.api-credentials.index', compact('credentials'));
    }

    /**
     * Update or create credential
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service' => 'required|string|in:careem,loyverse',
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
     * Toggle credential status
     */
    public function toggle(ApiCredential $apiCredential)
    {
        $apiCredential->update(['is_active' => !$apiCredential->is_active]);

        return back()->with('success', 'Credential status updated');
    }

    /**
     * Delete credential
     */
    public function destroy(ApiCredential $apiCredential)
    {
        $apiCredential->delete();

        return back()->with('success', 'Credential deleted successfully');
    }

    /**
     * Test Loyverse API connection
     */
    public function testConnection()
    {
        try {
            $result = $this->loyverseApiService->testConnection();

            if ($result) {
                return back()->with('success', 'Loyverse API connection successful!');
            } else {
                return back()->with('error', 'Loyverse API connection failed');
            }
        } catch (\Exception $e) {
            Log::error('Loyverse API test failed: ' . $e->getMessage());
            return back()->with('error', 'Connection test failed: ' . $e->getMessage());
        }
    }
}
