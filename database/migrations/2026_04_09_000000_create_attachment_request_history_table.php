<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachment_request_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('attachment_request_id');
            $table->foreign('attachment_request_id')->references('id')->on('attachment_requests')->onDelete('cascade');
            
            $table->uuid('attachment_request_item_id')->nullable();
            $table->foreign('attachment_request_item_id')->references('id')->on('attachment_request_items')->onDelete('cascade');
            
            // Action type: request_created, request_approved, request_declined, 
            // attachment_approved, attachment_declined, attachment_update_requested, etc.
            $table->string('action');
            
            // Human-readable description
            $table->text('description');
            
            // User who performed the action
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // Additional metadata (JSON)
            $table->json('metadata')->nullable();
            
            $table->timestamp('created_at');
            
            // Indexes
            $table->index('attachment_request_id');
            $table->index('attachment_request_item_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachment_request_history');
    }
};
