<?php

namespace Database\Factories;

use App\Models\MenuItem;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuItem>
 */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dishNames = [
            'Classic Burger', 'Chicken Sandwich', 'Vegetarian Wrap', 'Caesar Salad',
            'Pasta Primavera', 'Grilled Salmon', 'Beef Steak', 'Margherita Pizza',
            'Chocolate Cake', 'Tiramisu', 'Coffee Latte', 'Fresh Juice',
            'French Fries', 'Onion Rings', 'Garlic Bread', 'Soup of the Day',
            'Fruit Bowl', 'Protein Smoothie', 'Club Sandwich', 'Quesadilla',
        ];

        $name = $this->faker->randomElement($dishNames);
        
        return [
            'tenant_id' => fn() => \App\Models\Tenant::inRandomOrder()->first()?->id ?? \App\Models\Tenant::factory()->create()->id,
            'menu_id' => fn() => Menu::inRandomOrder()->first()?->id ?? Menu::factory()->create()->id,
            'name' => $name,
            'description' => $this->faker->sentence(8),
            'image_url' => 'menu-items/' . $this->faker->uuid() . '.jpg',
            'sku' => 'SKU-' . $this->faker->numerify('####'),
            'category' => $this->faker->randomElement(['Appetizers', 'Main Courses', 'Desserts', 'Beverages', 'Sides']),
            'loyverse_item_id' => $this->faker->numerify('##########'),
            'loyverse_variant_id' => $this->faker->numerify('##########'),
            'default_quantity' => $this->faker->numberBetween(1, 5),
            'price' => $this->faker->randomFloat(2, 5, 50),
            'tax_rate' => $this->faker->randomFloat(2, 0, 0.2),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create an active menu item.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive menu item.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a menu item for a specific menu.
     */
    public function forMenu(Menu $menu): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $menu->tenant_id,
            'menu_id' => $menu->id,
        ]);
    }

    /**
     * Create a cheap menu item.
     */
    public function cheap(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->randomFloat(2, 1, 15),
        ]);
    }

    /**
     * Create an expensive menu item.
     */
    public function expensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->randomFloat(2, 25, 80),
        ]);
    }

    /**
     * Create an appetizer.
     */
    public function appetizer(): static
    {
        $appetizers = ['Bruschetta', 'Garlic Bread', 'Caesar Salad', 'Soup of the Day', 'Spinach Dip'];
        
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement($appetizers),
            'category' => 'Appetizers',
            'price' => $this->faker->randomFloat(2, 5, 15),
        ]);
    }

    /**
     * Create a main course.
     */
    public function mainCourse(): static
    {
        $mains = ['Grilled Chicken', 'Beef Steak', 'Grilled Salmon', 'Pasta Carbonara', 'Vegetarian Lasagna'];
        
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement($mains),
            'category' => 'Main Courses',
            'price' => $this->faker->randomFloat(2, 15, 60),
        ]);
    }

    /**
     * Create a dessert.
     */
    public function dessert(): static
    {
        $desserts = ['Chocolate Cake', 'Tiramisu', 'Cheesecake', 'Ice Cream Sundae', 'Fruit Tart'];
        
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement($desserts),
            'category' => 'Desserts',
            'price' => $this->faker->randomFloat(2, 4, 12),
        ]);
    }

    /**
     * Create a beverage.
     */
    public function beverage(): static
    {
        $beverages = ['Coffee Latte', 'Fresh Juice', 'Iced Tea', 'Soft Drink', 'Smoothie'];
        
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement($beverages),
            'category' => 'Beverages',
            'price' => $this->faker->randomFloat(2, 2, 8),
        ]);
    }

    /**
     * Create a side dish.
     */
    public function side(): static
    {
        $sides = ['French Fries', 'Onion Rings', 'Garlic Bread', 'Coleslaw', 'Vegetables'];
        
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement($sides),
            'category' => 'Sides',
            'price' => $this->faker->randomFloat(2, 3, 10),
        ]);
    }

    /**
     * Create with modifier groups.
     */
    public function withModifiers(int $count = 2): static
    {
        return $this->has(
            \App\Models\ModifierGroup::factory()->count($count),
            'modifierGroups'
        );
    }

    /**
     * Create a popular item (higher price).
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->randomFloat(2, 25, 45),
            'is_active' => true,
        ]);
    }

    /**
     * Create a discounted item.
     */
    public function discount(): static
    {
        $price = $this->faker->randomFloat(2, 15, 40);
        
        return $this->state(fn (array $attributes) => [
            'price' => $price * 0.85, // 15% discount
            'description' => $attributes['description'] . ' (Special discount!)',
        ]);
    }
}
