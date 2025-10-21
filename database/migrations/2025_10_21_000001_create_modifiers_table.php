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
        Schema::create('modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_adjustment', 10, 2)->default(0.00)->comment('Price to add/subtract when modifier is selected');
            $table->string('loyverse_modifier_id')->nullable()->comment('Loyverse API modifier ID');
            $table->string('sku')->nullable()->comment('SKU for reference');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable()->comment('Additional data like allergens, calories, etc.');
            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('is_active');
            $table->index(['tenant_id', 'loyverse_modifier_id']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modifiers');
    }
};
