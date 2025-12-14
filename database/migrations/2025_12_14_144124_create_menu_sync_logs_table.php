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
        Schema::create('menu_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->string('platform'); // careem, talabat
            $table->string('action'); // sync_started, sync_completed, sync_failed
            $table->enum('status', ['pending', 'processing', 'success', 'failed']);
            $table->text('message')->nullable();
            $table->json('metadata')->nullable(); // Store catalog_id, brand_id, branch_id, errors, etc.
            $table->timestamps();
            
            $table->index(['tenant_id', 'menu_id']);
            $table->index(['platform', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_sync_logs');
    }
};
