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
            'account_configured' => $tenant->getSetting('currency') && $tenant->getSetting('timezone'),
            'location_created' => $tenant->locations()->exists(),
            'loyverse_connected' => $loyverseCredential && $loyverseCredential->is_active,
            'careem_configured' => $careemCredential && $careemCredential->is_active,
        ];

        // Pass additional data for forms
        $currencies = supportedCurrencies();
        $timezones = supportedTimezones();

        return view('dashboard.onboarding.index', compact('onboardingStatus', 'loyverseCredential', 'careemCredential', 'currencies', 'timezones', 'tenant'));
    }

    /**
     * Save account settings (currency and timezone).
     */
    public function saveAccountSettings(Request $request)
    {
        $request->validate([
            'currency' => 'required|string|in:' . implode(',', array_keys(supportedCurrencies())),
            'timezone' => 'required|string|timezone',
        ]);

        try {
            $tenant = app(TenantContext::class)->get();

            // Update tenant settings
            $tenant->updateSetting('currency', $request->currency);
            $tenant->updateSetting('timezone', $request->timezone);

            return redirect()
                ->route('dashboard.onboarding.index', ['subdomain' => request()->route('subdomain')])
                ->with('success', 'Account settings saved successfully!');

        } catch (\Exception $e) {
            Log::error('Account settings save failed during onboarding', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to save account settings. Please try again.');
        }
    }

    /**
     * Save location information.
     */
    public function saveLocation(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:2',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'in:careem,talabat',
        ]);

        try {
            $tenant = app(TenantContext::class)->get();

            // Create the location
            $tenant->locations()->create([
                'name' => $request->name,
                'address_line1' => $request->address_line1,
                'address_line2' => $request->address_line2,
                'city' => $request->city,
                'state' => $request->state,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'phone' => $request->phone,
                'email' => $request->email,
                'platforms' => $request->platforms,
                'is_active' => true,
            ]);

            return redirect()
                ->route('dashboard.onboarding.index', ['subdomain' => request()->route('subdomain')])
                ->with('success', 'Location created successfully!');

        } catch (\Exception $e) {
            Log::error('Location creation failed during onboarding', [
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Failed to create location. Please try again.');
        }
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
                ->route('dashboard.onboarding.index', ['subdomain' => request()->route('subdomain')])
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
                ->route('dashboard.onboarding.index', ['subdomain' => request()->route('subdomain')])
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

        // Verify minimum requirements
        if (!$tenant->getSetting('currency') || !$tenant->getSetting('timezone')) {
            return back()->with('error', 'Please configure your account settings before completing onboarding.');
        }

        if (!$tenant->locations()->exists()) {
            return back()->with('error', 'Please create at least one location before completing onboarding.');
        }

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
