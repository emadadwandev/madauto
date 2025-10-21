<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

class StripeWebhookController extends CashierController
{
    /**
     * Handle subscription created.
     */
    public function handleCustomerSubscriptionCreated(array $payload): void
    {
        try {
            $stripeSubscription = $payload['data']['object'];
            $stripeCustomerId = $stripeSubscription['customer'];

            $tenant = Tenant::where('stripe_id', $stripeCustomerId)->first();

            if ($tenant && $tenant->subscription) {
                $tenant->subscription->update([
                    'stripe_subscription_id' => $stripeSubscription['id'],
                    'status' => $stripeSubscription['status'],
                    'current_period_start' => \Carbon\Carbon::createFromTimestamp($stripeSubscription['current_period_start']),
                    'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSubscription['current_period_end']),
                ]);

                Log::info('Stripe subscription created', [
                    'tenant_id' => $tenant->id,
                    'stripe_subscription_id' => $stripeSubscription['id'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle customer.subscription.created', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Handle subscription updated.
     */
    public function handleCustomerSubscriptionUpdated(array $payload): void
    {
        try {
            $stripeSubscription = $payload['data']['object'];

            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription['id'])->first();

            if ($subscription) {
                $subscription->update([
                    'status' => $stripeSubscription['status'],
                    'current_period_start' => \Carbon\Carbon::createFromTimestamp($stripeSubscription['current_period_start']),
                    'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSubscription['current_period_end']),
                    'cancel_at_period_end' => $stripeSubscription['cancel_at_period_end'] ?? false,
                ]);

                Log::info('Stripe subscription updated', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $stripeSubscription['id'],
                    'status' => $stripeSubscription['status'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle customer.subscription.updated', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Handle subscription deleted (cancelled).
     */
    public function handleCustomerSubscriptionDeleted(array $payload): void
    {
        try {
            $stripeSubscription = $payload['data']['object'];

            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription['id'])->first();

            if ($subscription) {
                $subscription->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                Log::info('Stripe subscription cancelled', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $stripeSubscription['id'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle customer.subscription.deleted', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Handle successful invoice payment.
     */
    public function handleInvoicePaymentSucceeded(array $payload): void
    {
        try {
            $invoice = $payload['data']['object'];
            $stripeCustomerId = $invoice['customer'];
            $stripeSubscriptionId = $invoice['subscription'] ?? null;

            $tenant = Tenant::where('stripe_id', $stripeCustomerId)->first();

            if ($tenant && $stripeSubscriptionId) {
                $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

                if ($subscription && $subscription->status !== 'active') {
                    $subscription->update([
                        'status' => 'active',
                    ]);
                }

                Log::info('Invoice payment succeeded', [
                    'tenant_id' => $tenant->id,
                    'stripe_invoice_id' => $invoice['id'],
                    'amount' => $invoice['amount_paid'] / 100,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle invoice.payment_succeeded', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Handle failed invoice payment.
     */
    public function handleInvoicePaymentFailed(array $payload): void
    {
        try {
            $invoice = $payload['data']['object'];
            $stripeCustomerId = $invoice['customer'];
            $stripeSubscriptionId = $invoice['subscription'] ?? null;

            $tenant = Tenant::where('stripe_id', $stripeCustomerId)->first();

            if ($tenant && $stripeSubscriptionId) {
                $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

                if ($subscription) {
                    $subscription->update([
                        'status' => 'past_due',
                    ]);
                }

                // TODO: Send payment failed notification email
                // Mail::to($tenant->adminEmail())->send(new PaymentFailedMail($tenant));

                Log::warning('Invoice payment failed', [
                    'tenant_id' => $tenant->id,
                    'stripe_invoice_id' => $invoice['id'],
                    'amount' => $invoice['amount_due'] / 100,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle invoice.payment_failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Handle payment method updated.
     */
    public function handleCustomerUpdated(array $payload): void
    {
        try {
            $customer = $payload['data']['object'];

            $tenant = Tenant::where('stripe_id', $customer['id'])->first();

            if ($tenant) {
                // Update payment method details if needed
                $paymentMethod = $customer['invoice_settings']['default_payment_method'] ?? null;

                Log::info('Customer updated', [
                    'tenant_id' => $tenant->id,
                    'stripe_customer_id' => $customer['id'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle customer.updated', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }
}
