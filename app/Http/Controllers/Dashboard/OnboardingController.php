<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Repositories\ApiCredentialRepository;
use App\Services\LoyverseApiService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    protected $apiCredentialRepository;

    protected $loyverseApiService;

    public function __construct(
        ApiCredentialRepository $apiCredentialRepository,
        LoyverseApiService $loyverseApiService
    ) {
        $this->apiCredentialRepository = $apiCredentialRepository;
        $this->loyverseApiService = $loyverseApiService;
    }

    /**
     * Display the onboarding wizard.
     */
    public function index()
    {
        $tenant = app(TenantContext::class)->get();

        // If already onboarded, redirect to dashboard
        if ($tenant->onboarding_completed_at) {
            return redirect('/dashboard')->with('info', 'You have already completed onboarding!');
        }

        $loyverseCredential = $this->apiCredentialRepository->getByService('loyverse');
        $careemCredential = $this->apiCredentialRepository->getByService('careem');

        $onboardingStatus = [
            'loyverse_connected' => $loyverseCredential && $loyverseCredential->is_active,
            'careem_configured' => $careemCredential && $careemCredential->is_active,
        ];

        return view('dashboard.onboarding.index', compact('onboardingStatus', 'loyverseCredential', 'careemCredential'));
    }

    /**
     * Save and test Loyverse API token.
     */
    public function saveLoyverseToken(Request $request)
    {
        $request->validate([
            'api_token' => 'required|string',
        ]);

        try {
            // Test the token by attempting to fetch stores
            $testResult = $this->loyverseApiService->testConnection($request->api_token);

            if (! $testResult['success']) {
                return back()->with('error', 'Invalid Loyverse API token: '.$testResult['message']);
            }

            // Save the credential
            $this->apiCredentialRepository->createOrUpdate('loyverse', [
                'api_token' => $request->api_token,
                'is_active' => true,
            ]);

            return redirect()
                ->route('dashboard.onboarding.index')
                ->with('success', 'Loyverse connected successfully! Connection test passed.');

        } catch (\Exception $e) {
            Log::error('Loyverse connection failed during onboarding', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to connect to Loyverse. Please check your API token and try again.');
        }
    }

    /**
     * Generate Careem webhook secret.
     */
    public function generateWebhookSecret(Request $request)
    {
        try {
            $secret = Str::random(64);

            // Save the webhook secret
            $this->apiCredentialRepository->createOrUpdate('careem', [
                'webhook_secret' => $secret,
                'is_active' => true,
            ]);

            return redirect()
                ->route('dashboard.onboarding.index')
                ->with('success', 'Careem webhook configured successfully!');

        } catch (\Exception $e) {
            Log::error('Webhook secret generation failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to generate webhook secret. Please try again.');
        }
    }

    /**
     * Mark onboarding as complete.
     */
    public function complete()
    {
        $tenant = app(TenantContext::class)->get();

        // Verify that at least Loyverse is connected
        $loyverseCredential = $this->apiCredentialRepository->getByService('loyverse');

        if (! $loyverseCredential || ! $loyverseCredential->is_active) {
            return back()->with('error', 'Please connect your Loyverse account before completing onboarding.');
        }

        // Mark onboarding as complete
        $tenant->update([
            'onboarding_completed_at' => now(),
        ]);

        return redirect('/dashboard')
            ->with('success', 'Onboarding complete! Welcome to your dashboard.');
    }

    /**
     * Skip onboarding (optional).
     */
    public function skip()
    {
        $tenant = app(TenantContext::class)->get();

        $tenant->update([
            'onboarding_completed_at' => now(),
        ]);

        return redirect('/dashboard')
            ->with('info', 'You can complete setup from the API Credentials page anytime.');
    }
}
