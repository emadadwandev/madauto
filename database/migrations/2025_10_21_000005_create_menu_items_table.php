<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('sku')->nullable();
            $table->integer('default_quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0.00)->comment('Tax percentage (e.g., 5.00 for 5%)');
            $table->string('loyverse_item_id')->nullable()->comment('Loyverse API item ID');
            $table->string('loyverse_variant_id')->nullable()->comment('Loyverse API variant ID');
            $table->string('category')->nullable()->comment('Item category/group');
            $table->integer('sort_order')->default(0)->comment('Display order in menu');
            $table->boolean('is_available')->default(true)->comment('Currently available for ordering');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable()->comment('Additional data like calories, allergens, preparation time, etc.');
            $table->timestamps();

            // Indexes
            $table->index('menu_id');
            $table->index('tenant_id');
            $table->index('sku');
            $table->index('is_active');
            $table->index('is_available');
            $table->index(['menu_id', 'is_active', 'is_available']);
            $table->index(['tenant_id', 'loyverse_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
