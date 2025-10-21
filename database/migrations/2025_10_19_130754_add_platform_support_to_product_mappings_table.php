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
        Schema::table('product_mappings', function (Blueprint $table) {
            // Add platform column
            $table->enum('platform', ['careem', 'talabat'])->default('careem')->after('id');

            // Drop old unique constraint on careem_product_id
            $table->dropUnique(['careem_product_id']);

            // Rename columns to be platform-agnostic
            $table->renameColumn('careem_product_id', 'platform_product_id');
            $table->renameColumn('careem_sku', 'platform_sku');
            $table->renameColumn('careem_name', 'platform_name');
        });

        // Add new composite unique constraint and indexes
        Schema::table('product_mappings', function (Blueprint $table) {
            $table->unique(['platform', 'platform_product_id'], 'unique_platform_product');
            $table->index('platform');
            $table->index(['platform', 'platform_sku'], 'idx_platform_sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_mappings', function (Blueprint $table) {
            // Drop new indexes
            $table->dropUnique('unique_platform_product');
            $table->dropIndex('idx_platform_sku');
            $table->dropIndex(['platform']);

            // Rename columns back
            $table->renameColumn('platform_product_id', 'careem_product_id');
            $table->renameColumn('platform_sku', 'careem_sku');
            $table->renameColumn('platform_name', 'careem_name');
        });

        // Restore old unique constraint and drop platform column
        Schema::table('product_mappings', function (Blueprint $table) {
            $table->unique('careem_product_id');
            $table->dropColumn('platform');
        });
    }
};
