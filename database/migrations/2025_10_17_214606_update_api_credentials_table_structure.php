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
        Schema::table('api_credentials', function (Blueprint $table) {
            // Drop old column
            $table->dropColumn('credentials');

            // Add new columns
            $table->string('credential_type')->after('service');
            $table->text('credential_value')->after('credential_type');

            // Add unique constraint
            $table->unique(['service', 'credential_type'], 'unique_service_credential');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_credentials', function (Blueprint $table) {
            // Drop new columns
            $table->dropUnique('unique_service_credential');
            $table->dropColumn(['credential_type', 'credential_value']);

            // Restore old column
            $table->text('credentials')->after('service');
        });
    }
};
