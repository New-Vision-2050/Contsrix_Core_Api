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
        Schema::create('attendance_constraint_violations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('user_id');
            $table->uuid('attendance_id');
            $table->uuid('constraint_id');
            
            $table->string('violation_type'); // location_violation, time_violation, etc.
            $table->json('violation_details'); // detailed information about the violation
            $table->enum('severity_level', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['pending', 'acknowledged', 'resolved', 'dismissed'])->default('pending');
            
            $table->uuid('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            
            $table->boolean('auto_resolved')->default(false);
            $table->boolean('notification_sent')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'user_id']);
            $table->index(['company_id', 'violation_type']);
            $table->index(['company_id', 'severity_level']);
            $table->index(['attendance_id']);
            $table->index(['constraint_id']);
            $table->index(['status', 'severity_level']);
            $table->index(['created_at']);
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('cascade');
            $table->foreign('constraint_id')->references('id')->on('attendance_constraints')->onDelete('cascade');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_constraint_violations');
    }
};
