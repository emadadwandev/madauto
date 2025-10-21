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
        Schema::create('menu_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->boolean('is_active')->default(true)->comment('Enable/disable menu for this location');
            $table->json('override_settings')->nullable()->comment('Location-specific pricing or availability overrides');
            $table->timestamps();

            // Unique constraint - each menu can only be assigned to a location once
            $table->unique(['menu_id', 'location_id'], 'unique_menu_location');

            // Indexes
            $table->index('menu_id');
            $table->index('location_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_location');
    }
};
