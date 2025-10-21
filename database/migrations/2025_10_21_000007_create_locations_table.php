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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('AE')->comment('ISO country code');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->json('platforms')->comment('Connected platforms: ["careem", "talabat"]');
            $table->json('opening_hours')->nullable()->comment('Hours per day: {monday: {open: "09:00", close: "22:00"}, ...}');
            $table->boolean('is_busy')->default(false)->comment('Temporary pause - not accepting orders');
            $table->boolean('is_active')->default(true)->comment('Permanent active/inactive status');
            $table->string('loyverse_store_id')->nullable()->comment('Loyverse API store ID');
            $table->json('metadata')->nullable()->comment('Additional settings like delivery zones, fees, etc.');
            $table->timestamps();

            // Indexes
            $table->index('tenant_id');
            $table->index('is_active');
            $table->index('is_busy');
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'loyverse_store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
