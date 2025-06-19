<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeaveRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Employee reference
            $table->string('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Management hierarchy reference for department/branch tracking
            $table->unsignedBigInteger('management_hierarchy_id')->nullable();
            $table->foreign('management_hierarchy_id')->references('id')->on('management_hierarchies')->onDelete('set null');
            
            // Leave type reference
            $table->unsignedBigInteger('leave_type_id');
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
            
            // Leave request details
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('leave_duration_type', ['full_day', 'first_half', 'second_half'])->default('full_day');
            $table->decimal('total_days', 8, 2);
            $table->text('reason')->nullable();
            
            // Approval process fields
            $table->string('supervisor_id')->nullable();
            $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->dateTime('supervisor_action_date')->nullable();
            
            // HR approval fields (for multi-level approval)
            $table->string('hr_approver_id')->nullable();
            $table->foreign('hr_approver_id')->references('id')->on('users')->onDelete('set null');
            $table->dateTime('hr_action_date')->nullable();
            $table->enum('hr_status', ['pending', 'approved', 'rejected'])->nullable();
            
            // Attachment and notes
            $table->string('attachment_path')->nullable();
            $table->text('supervisor_comments')->nullable();
            $table->text('hr_comments')->nullable();
            $table->text('cancel_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leave_requests');
    }
}
