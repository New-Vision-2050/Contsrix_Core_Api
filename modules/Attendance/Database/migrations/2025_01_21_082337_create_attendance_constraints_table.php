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
        Schema::create('attendance_constraints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('user_id')->nullable(); // null means applies to all users in company
            $table->uuid('department_id')->nullable(); // null means applies to all departments
            
            $table->string('constraint_type'); // location, time, device, role, behavioral, security, compliance
            $table->string('constraint_name'); // specific constraint name within type
            $table->json('constraint_config'); // configuration parameters for the constraint
            
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(1); // higher number = higher priority
            
            $table->date('start_date')->nullable(); // when constraint becomes active
            $table->date('end_date')->nullable(); // when constraint expires
            
            $table->uuid('created_by');
            $table->uuid('updated_by')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'user_id']);
            $table->index(['company_id', 'constraint_type']);
            $table->index(['company_id', 'constraint_name']);
            $table->index(['priority', 'is_active']);
            $table->index(['start_date', 'end_date']);
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_constraints');
    }
};
