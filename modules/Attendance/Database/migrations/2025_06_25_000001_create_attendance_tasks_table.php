<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->comment('User who the task is about');
            $table->uuid('constraint_id')->nullable()->comment('Related constraint ID');
            $table->uuid('attendance_id')->nullable()->comment('Related attendance record');
            $table->uuid('company_id')->comment('Company ID for multi-tenancy');
            $table->string('type')->comment('Task type (constraint_exception, temporary_location, etc.)');
            $table->json('details')->nullable()->comment('Task-specific details');
            $table->string('status')->default('pending')->comment('Task status (pending, in_progress, completed, rejected)');
            $table->string('priority')->default('medium')->comment('Task priority (low, medium, high, critical)');
            $table->uuid('assigned_to')->nullable()->comment('User ID assigned to handle this task');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->text('completion_notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->index('due_date');
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_tasks');
    }
}
