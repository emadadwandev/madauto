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
        Schema::table('locations', function (Blueprint $table) {
            $table->string('careem_store_id')->nullable()->after('loyverse_store_id')
                ->comment('Careem store/branch ID for Store API');
            $table->string('talabat_vendor_id')->nullable()->after('careem_store_id')
                ->comment('Talabat POS vendor ID for availability API');
            $table->json('platform_sync_status')->nullable()->after('talabat_vendor_id')
                ->comment('Sync status per platform: {careem: {last_sync: ..., status: ...}, talabat: {...}}');

            // Add indexes for platform IDs
            $table->index('careem_store_id');
            $table->index('talabat_vendor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['careem_store_id']);
            $table->dropIndex(['talabat_vendor_id']);
            $table->dropColumn(['careem_store_id', 'talabat_vendor_id', 'platform_sync_status']);
        });
    }
};
