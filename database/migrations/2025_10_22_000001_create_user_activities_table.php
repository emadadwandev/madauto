<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            
            // Tenant scoping
            $table->uuid('tenant_id')->nullable();
            
            // Who performed the action (context user)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            
            // Action details
            $table->string('action'); // user.login, user.invited, menu.created, etc.
            $table->text('description')->nullable();
            
            // Request info
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Additional context data
            $table->json('properties')->nullable();
            
            // Who actually triggered the action (could be admin acting on user)
            $table->nullableMorphs('causer');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'action']);
            $table->index(['tenant_id', 'created_at']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
