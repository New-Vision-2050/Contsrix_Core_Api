<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachment_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('serial_number')->unique();
            $table->string('name');
            $table->date('date');
            
            // Project reference
            $table->uuid('project_id');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            
            // Companies involved
            $table->uuid('sender_company_id');
            $table->foreign('sender_company_id')->references('id')->on('companies')->onDelete('cascade');
            
            $table->uuid('receiver_company_id');
            $table->foreign('receiver_company_id')->references('id')->on('companies')->onDelete('cascade');
            
            // Attachment type hierarchy
            $table->unsignedBigInteger('attachment_type_id')->nullable();
            $table->unsignedBigInteger('attachment_sub_type_id')->nullable();
            $table->unsignedBigInteger('attachment_sub_sub_type_id')->nullable();
            
            // Request status: pending, semi-approved, approved, declined
            $table->string('status')->default('pending');
            
            // User who created the request
            $table->uuid('created_by_user_id')->nullable();
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('set null');
            
            // User who responded to the request
            $table->uuid('responded_by_user_id')->nullable();
            $table->foreign('responded_by_user_id')->references('id')->on('users')->onDelete('set null');
            
            $table->timestamp('responded_at')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('project_id');
            $table->index('sender_company_id');
            $table->index('receiver_company_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachment_requests');
    }
};
