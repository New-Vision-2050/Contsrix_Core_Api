<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('company_id');
            $table->uuid('leave_type_id');
            
            // Leave period
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days');
            
            // Request details
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])
                  ->default('pending');
            
            // Request tracking
            $table->uuid('requested_by');
            $table->timestamp('requested_at')->useCurrent();
            
            // Approval tracking
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            // Rejection tracking
            $table->uuid('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Special flags
            $table->boolean('is_emergency')->default(false);
            
            // Attachments and notes
            $table->json('attachments')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['user_id', 'start_date']);
            $table->index(['company_id', 'status']);
            $table->index(['leave_type_id']);
            $table->index(['status']);
            $table->index(['start_date', 'end_date']);
            $table->index(['is_emergency']);
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
};
