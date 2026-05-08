<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachment_request_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('attachment_request_id');
            $table->foreign('attachment_request_id')->references('id')->on('attachment_requests')->onDelete('cascade');
            
            // Attachment file information
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            
            // Item status: pending, approved, declined, update_requested
            $table->string('status')->default('pending');
            
            // Response details
            $table->uuid('responded_by_user_id')->nullable();
            $table->foreign('responded_by_user_id')->references('id')->on('users')->onDelete('set null');
            
            $table->timestamp('responded_at')->nullable();
            $table->text('response_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('attachment_request_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachment_request_items');
    }
};
