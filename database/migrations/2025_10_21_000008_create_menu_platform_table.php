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
        Schema::create('menu_platform', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->enum('platform', ['careem', 'talabat'])->comment('Delivery platform');
            $table->enum('sync_status', ['pending', 'syncing', 'synced', 'failed'])->default('pending');
            $table->timestamp('published_at')->nullable()->comment('When menu was published to this platform');
            $table->timestamp('last_synced_at')->nullable()->comment('Last successful sync');
            $table->text('sync_error')->nullable()->comment('Last sync error message');
            $table->json('platform_menu_id')->nullable()->comment('Platform-specific menu ID returned after sync');
            $table->timestamps();

            // Unique constraint - each menu can only be assigned to a platform once
            $table->unique(['menu_id', 'platform'], 'unique_menu_platform');

            // Indexes
            $table->index('menu_id');
            $table->index('platform');
            $table->index('sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_platform');
    }
};
