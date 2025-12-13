<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Menu>
 */
class MenuFactory extends Factory
{
    protected $model = Menu::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'tenant_id' => fn () => Tenant::inRandomOrder()->first()?->id ?? Tenant::factory()->create()->id,
            'name' => $name,
            'description' => $this->faker->sentence(),
            'image_url' => 'menus/'.$this->faker->uuid().'.jpg',
            'status' => 'draft',
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'published_at' => $this->faker->boolean(50) ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create a published menu.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Create a draft menu.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Create an active menu.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive menu.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a menu for a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Create a menu with items.
     */
    public function withItems(int $count = 5): static
    {
        return $this->has(\App\Models\MenuItem::factory()->count($count), 'items');
    }

    /**
     * Create a menu with locations.
     */
    public function withLocations(int $count = 2): static
    {
        return $this->hasAttached(\App\Models\Location::factory()->count($count), [], function ($location) {
            return ['is_active' => true];
        });
    }

    /**
     * Create a restaurant dinner menu.
     */
    public function restaurant(): static
    {
        $name = $this->faker->randomElement(['Signature Dishes', 'Chef Specials', 'Classic Favorites', 'Gourmet Selection']);

        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'description' => 'Discover our carefully curated selection of exceptional dishes crafted with the finest ingredients.',
        ]);
    }

    /**
     * Create a breakfast menu.
     */
    public function breakfast(): static
    {
        $name = $this->faker->randomElement(['Morning Delights', 'Breakfast Specials', 'Sunrise Menu', 'Early Bird Favorites']);

        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'description' => 'Start your day with our delicious breakfast options, prepared fresh every morning.',
        ]);
    }

    /**
     * Create a cafe menu.
     */
    public function cafe(): static
    {
        $name = $this->faker->randomElement(['Coffee & Snacks', 'Cafe Favorites', 'Brew & Bite Menu', 'Cozy Corner Selection']);

        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'description' => 'Artisan coffee, freshly baked pastries, and light bites perfect for any time of day.',
        ]);
    }
}
