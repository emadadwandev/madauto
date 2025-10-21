<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'price' => 29.00,
                'currency' => 'USD',
                'billing_interval' => 'month',
                'order_limit' => 500,
                'location_limit' => 1,
                'user_limit' => 1,
                'features' => [
                    'Up to 500 orders per month',
                    '1 Loyverse location',
                    '1 team member',
                    'Automatic order sync',
                    'Product mapping',
                    'Email support',
                    '14-day free trial',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'price' => 79.00,
                'currency' => 'USD',
                'billing_interval' => 'month',
                'order_limit' => 2000,
                'location_limit' => 3,
                'user_limit' => 5,
                'features' => [
                    'Up to 2,000 orders per month',
                    'Up to 3 Loyverse locations',
                    'Up to 5 team members',
                    'Automatic order sync',
                    'Product mapping',
                    'Priority email support',
                    'Advanced analytics',
                    '14-day free trial',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price' => 199.00,
                'currency' => 'USD',
                'billing_interval' => 'month',
                'order_limit' => null, // Unlimited
                'location_limit' => null, // Unlimited
                'user_limit' => null, // Unlimited
                'features' => [
                    'Unlimited orders',
                    'Unlimited locations',
                    'Unlimited team members',
                    'Automatic order sync',
                    'Product mapping',
                    '24/7 priority support',
                    'Advanced analytics',
                    'Custom integrations',
                    'Dedicated account manager',
                    '14-day free trial',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }

        $this->command->info('Subscription plans seeded successfully!');
    }
}
