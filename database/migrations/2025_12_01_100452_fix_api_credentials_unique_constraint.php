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
            // Drop old unique constraint that doesn't include tenant_id
            $table->dropUnique('unique_service_credential');

            // Add new unique constraint that includes tenant_id
            $table->unique(['tenant_id', 'service', 'credential_type'], 'unique_tenant_service_credential');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_credentials', function (Blueprint $table) {
            // Drop new constraint
            $table->dropUnique('unique_tenant_service_credential');

            // Restore old constraint (without tenant_id)
            $table->unique(['service', 'credential_type'], 'unique_service_credential');
        });
    }
};
