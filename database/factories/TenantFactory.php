<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();
        $subdomain = strtolower(str_replace(' ', '', $name)).rand(100, 999);

        return [
            'name' => $name,
            'subdomain' => $subdomain,
            'domain' => null,
            'status' => 'active',
            'trial_ends_at' => now()->addDays(14),
            'settings' => json_encode([
                'timezone' => $this->faker->timezone(),
                'currency' => 'USD',
                'locale' => 'en',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the tenant is on trial.
     */
    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    /**
     * Indicate that the tenant is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'trial_ends_at' => null,
        ]);
    }

    /**
     * Indicate that the tenant is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the tenant is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Create a tenant with a specific subdomain.
     */
    public function withSubdomain(string $subdomain): static
    {
        return $this->state(fn (array $attributes) => [
            'subdomain' => $subdomain,
        ]);
    }

    /**
     * Create a tenant with subscription.
     */
    public function withSubscription(): static
    {
        return $this->has(
            \App\Models\Subscription::factory(),
            'subscription'
        );
    }
}
