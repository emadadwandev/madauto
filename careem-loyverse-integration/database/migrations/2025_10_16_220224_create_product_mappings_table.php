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
        Schema::create('product_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('careem_product_id')->unique();
            $table->string('careem_sku')->nullable()->index();
            $table->string('careem_name');
            $table->string('loyverse_item_id');
            $table->string('loyverse_variant_id')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index('careem_product_id');
            $table->index(['loyverse_item_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_mappings');
    }
};
