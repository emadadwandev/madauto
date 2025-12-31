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
        Schema::create('careem_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id');
            $table->string('careem_item_id')->index();
            $table->string('careem_catalog_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->nullable()->index();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('AED');
            $table->string('category_id')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->string('image_url')->nullable();
            $table->json('modifier_group_ids')->nullable();
            $table->string('external_id')->nullable(); // Loyverse UUID
            $table->json('raw_data')->nullable(); // Store full API response
            $table->timestamps();

            // Composite unique index: one item ID per tenant
            $table->unique(['tenant_id', 'careem_item_id']);

            // Foreign key
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('careem_catalog_items');
    }
};
