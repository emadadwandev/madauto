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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Starter', 'Business', 'Enterprise'
            $table->string('slug')->unique(); // e.g., 'starter', 'business', 'enterprise'
            $table->decimal('price', 10, 2); // Monthly price
            $table->string('currency', 3)->default('USD');
            $table->enum('billing_interval', ['month', 'year'])->default('month');
            $table->integer('order_limit')->nullable(); // null = unlimited
            $table->integer('location_limit')->nullable(); // null = unlimited
            $table->integer('user_limit')->nullable(); // null = unlimited
            $table->json('features')->nullable(); // Array of features
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
