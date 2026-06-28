<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectNotificationTaskPostponements extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_notification_task_postponements')) {
            return;
        }

        Schema::create('project_notification_task_postponements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('project_notification_id');
            $table->uuid('employee_task_request_id')->nullable();
            $table->uuid('process_id')->nullable();
            $table->uuid('procedure_setting_id')->nullable();
            $table->date('previous_task_date')->nullable();
            $table->time('previous_task_time')->nullable();
            $table->date('new_task_date')->nullable();
            $table->time('new_task_time')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 30)->default('pending');
            $table->uuid('requested_by')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'project_notification_id'], 'pntp_company_notification_idx');
            $table->index('employee_task_request_id', 'pntp_task_request_idx');
            $table->index('process_id', 'pntp_process_idx');
            $table->index('status', 'pntp_status_idx');

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('project_notification_id')->references('id')->on('project_notifications')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notification_task_postponements');
    }
}
