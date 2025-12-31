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
        Schema::table('orders', function (Blueprint $table) {
            // Add platform_status column to track order status on delivery platform (Careem/Talabat)
            // This is separate from internal 'status' which tracks Loyverse sync status
            $table->string('platform_status', 50)->nullable()->after('status')->index();
            $table->timestamp('platform_status_updated_at')->nullable()->after('platform_status');

            // Add indexes for better query performance
            $table->index(['tenant_id', 'platform_status']);
            $table->index(['tenant_id', 'status', 'platform_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status', 'platform_status']);
            $table->dropIndex(['tenant_id', 'platform_status']);
            $table->dropColumn(['platform_status', 'platform_status_updated_at']);
        });
    }
};
