<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use App\Services\UsageTrackingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionLimits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantContext = app(TenantContext::class);
        $tenant = $tenantContext->get();

        if (! $tenant) {
            // No tenant context, skip check
            return $next($request);
        }

        $subscription = $tenant->subscription;

        if (! $subscription) {
            // No subscription, redirect to subscribe page
            return redirect()->route('dashboard.subscription.plans', ['subdomain' => $tenant->subdomain])
                ->with('error', 'Please subscribe to a plan to continue.');
        }

        // Check if subscription is active or in trial
        if (! $subscription->canUse()) {
            $message = 'Your subscription is not active.';

            if ($subscription->isPastDue()) {
                $message = 'Your subscription payment is past due. Please update your payment method.';
            } elseif ($subscription->isCancelled()) {
                $message = 'Your subscription has been cancelled. Please resubscribe to continue.';
            }

            return redirect()->route('dashboard.subscription.index', ['subdomain' => $tenant->subdomain])
                ->with('error', $message);
        }

        // Check usage limits using UsageTrackingService
        $usageService = app(UsageTrackingService::class);

        if (! $usageService->withinLimits($tenant)) {
            $stats = $usageService->getUsageStats($tenant);

            return redirect()->route('dashboard.subscription.index', ['subdomain' => $tenant->subdomain])
                ->with('error', sprintf(
                    'You have reached your monthly order limit (%d/%d orders used). Please upgrade your plan.',
                    $stats['current_usage'],
                    $stats['limit']
                ));
        }

        return $next($request);
    }
}
