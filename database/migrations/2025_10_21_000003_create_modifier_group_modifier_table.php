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
        Schema::create('modifier_group_modifier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_group_id')->constrained('modifier_groups')->onDelete('cascade');
            $table->foreignId('modifier_id')->constrained('modifiers')->onDelete('cascade');
            $table->integer('sort_order')->default(0)->comment('Display order within the group');
            $table->boolean('is_default')->default(false)->comment('Pre-selected by default');
            $table->timestamps();

            // Unique constraint - each modifier can only be in a group once
            $table->unique(['modifier_group_id', 'modifier_id'], 'unique_modifier_in_group');

            // Indexes
            $table->index('modifier_group_id');
            $table->index('modifier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modifier_group_modifier');
    }
};
