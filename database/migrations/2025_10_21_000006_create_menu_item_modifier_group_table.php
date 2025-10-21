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
        Schema::create('menu_item_modifier_group', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->onDelete('cascade');
            $table->foreignId('modifier_group_id')->constrained('modifier_groups')->onDelete('cascade');
            $table->integer('sort_order')->default(0)->comment('Display order of modifier groups for this item');
            $table->timestamps();

            // Unique constraint - each modifier group can only be assigned to an item once
            $table->unique(['menu_item_id', 'modifier_group_id'], 'unique_group_per_item');

            // Indexes
            $table->index('menu_item_id');
            $table->index('modifier_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_item_modifier_group');
    }
};
