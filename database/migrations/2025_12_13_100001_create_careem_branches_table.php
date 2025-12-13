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
        Schema::create('careem_branches', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_id', 36); // UUID
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreignId('careem_brand_id')->constrained('careem_brands')->cascadeOnDelete();

            // Location Mapping
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();

            // Careem Branch Details
            $table->string('careem_branch_id')->unique(); // Unique ID for Careem API
            $table->string('name'); // Branch name (e.g., "KFC, Marina Mall")
            $table->string('state')->default('UNMAPPED'); // UNMAPPED, MAPPED

            // POS Integration Status
            $table->boolean('pos_integration_enabled')->default(false);

            // Visibility Status on SuperApp
            $table->integer('visibility_status')->default(2); // 1 = Active, 2 = Inactive

            // Metadata
            $table->json('metadata')->nullable(); // Additional branch information from Careem
            $table->timestamp('synced_at')->nullable(); // Last sync with Careem

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'careem_brand_id']);
            $table->index(['tenant_id', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('careem_branches');
    }
};
