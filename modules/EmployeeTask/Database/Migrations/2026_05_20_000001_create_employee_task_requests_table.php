<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_task_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('user_id');
            $table->string('serial_number', 50)->unique();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->uuid('project_id')->nullable();
            $table->uuid('approval_responsible_id')->nullable();
            $table->uuid('assignment_responsible_id')->nullable();
            $table->decimal('duration_hours', 6, 2);
            $table->decimal('original_duration_hours', 6, 2)->nullable();
            $table->date('task_date');
            $table->decimal('task_latitude', 10, 7);
            $table->decimal('task_longitude', 10, 7);
            $table->integer('radius_meters')->nullable();
            $table->uuid('procedure_setting_id')->nullable();
            $table->string('status', 30)->default('pending');
            $table->dateTime('time_from')->nullable();
            $table->dateTime('time_to')->nullable();
            $table->decimal('total_task_hours', 8, 2)->nullable();
            $table->integer('total_pause_minutes')->default(0);
            $table->string('shift_end_method', 30)->nullable();
            $table->json('start_location')->nullable();
            $table->json('end_location')->nullable();
            $table->string('timezone', 50)->nullable();
            $table->text('notes')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->uuid('rejected_by')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->uuid('cancelled_by')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('last_extension_status', 30)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'user_id'], 'etr_company_user_idx');
            $table->index(['company_id', 'task_date'], 'etr_company_date_idx');
            $table->index(['company_id', 'status'], 'etr_company_status_idx');
            $table->index(['user_id', 'task_date'], 'etr_user_date_idx');

            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_task_requests');
    }
};
