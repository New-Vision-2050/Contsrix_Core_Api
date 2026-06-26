<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('project_id');
            $table->uuid('employee_task_request_id')->nullable();
            $table->string('notification_number', 50);
            $table->string('notification_type')->nullable();
            $table->string('severity', 20)->default('منخفض');
            $table->string('work_type')->nullable();
            $table->string('magdy_number')->nullable();
            $table->text('work_description')->nullable();
            $table->string('contractor_name')->nullable();
            $table->string('contractor_number')->nullable();
            $table->string('contractor_technical_number')->nullable();
            $table->string('contractor_category')->nullable();
            $table->text('contractor_notes')->nullable();
            $table->string('contractor_mobile', 30)->nullable();
            $table->decimal('task_latitude', 10, 7)->nullable();
            $table->decimal('task_longitude', 10, 7)->nullable();
            $table->integer('location_radius')->nullable();
            $table->string('location_link')->nullable();
            $table->string('repair_point')->nullable();
            $table->uuid('assigned_user_id')->nullable();
            $table->integer('selected_distance_meters')->nullable();
            $table->string('status', 30)->default('pending');
            $table->uuid('created_by_user_id');
            $table->uuid('approved_by')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->uuid('rejected_by')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->date('task_date')->nullable();
            $table->decimal('duration_hours', 6, 2)->default(0);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['company_id', 'project_id'], 'pn_company_project_idx');
            $table->index(['company_id', 'status'], 'pn_company_status_idx');
            $table->index('employee_task_request_id', 'pn_task_request_idx');
            $table->index('assigned_user_id', 'pn_assigned_user_idx');
            $table->unique(['company_id', 'notification_number'], 'pn_company_number_uniq');

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('assigned_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notifications');
    }
};
