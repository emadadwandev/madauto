<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => function () {
                return \App\Models\Tenant::inRandomOrder()->first()?->id
                    ?? \App\Models\Tenant::factory()->create()->id;
            },
            'careem_order_id' => 'CAREEM-'.$this->faker->unique()->numerify('######'),
            'order_data' => [
                'order_id' => 'CAREEM-'.$this->faker->unique()->numerify('######'),
                'customer' => [
                    'name' => $this->faker->name(),
                    'phone' => $this->faker->phoneNumber(),
                ],
                'delivery_address' => [
                    'address_line' => $this->faker->streetAddress(),
                    'city' => $this->faker->city(),
                    'postal_code' => $this->faker->postcode(),
                ],
                'items' => [
                    [
                        'product_id' => 'PROD-'.$this->faker->numerify('####'),
                        'name' => $this->faker->words(2, true),
                        'sku' => 'SKU-'.$this->faker->numerify('####'),
                        'quantity' => $this->faker->numberBetween(1, 5),
                        'unit_price' => $this->faker->randomFloat(2, 5, 100),
                    ],
                ],
                'pricing' => [
                    'subtotal' => $this->faker->randomFloat(2, 20, 500),
                    'tax' => $this->faker->randomFloat(2, 1, 50),
                    'total' => $this->faker->randomFloat(2, 25, 550),
                ],
                'delivery_notes' => $this->faker->sentence(),
            ],
            'status' => $this->faker->randomElement(['pending', 'processing', 'synced', 'failed']),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create a pending order.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Create a processing order.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Create a synced order.
     */
    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'synced',
            'updated_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Create a failed order.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'updated_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Create an order with Loyverse sync.
     */
    public function withLoyverseSync(): static
    {
        return $this->has(
            \App\Models\LoyverseOrder::factory(),
            'loyverseOrder'
        );
    }

    /**
     * Create an order for a specific tenant.
     */
    public function forTenant(\App\Models\Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Create a high-value order.
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_data' => array_merge($attributes['order_data'], [
                'pricing' => [
                    'subtotal' => $this->faker->randomFloat(2, 200, 1000),
                    'tax' => $this->faker->randomFloat(2, 20, 100),
                    'total' => $this->faker->randomFloat(2, 220, 1100),
                ],
            ]),
        ]);
    }
}
