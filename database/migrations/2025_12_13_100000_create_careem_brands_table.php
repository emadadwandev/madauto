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
        Schema::create('careem_brands', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_id', 36); // UUID
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            // Careem Brand Details
            $table->string('careem_brand_id')->unique(); // Unique ID for Careem API (e.g., "KFC")
            $table->string('name'); // Brand name
            $table->string('state')->default('UNMAPPED'); // UNMAPPED, MAPPED

            // Metadata
            $table->json('metadata')->nullable(); // Additional brand information from Careem
            $table->timestamp('synced_at')->nullable(); // Last sync with Careem

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'careem_brand_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('careem_brands');
    }
};
