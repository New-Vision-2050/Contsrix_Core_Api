<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectNotificationWorkResumptions extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_notification_work_resumptions')) {
            return;
        }

        Schema::create('project_notification_work_resumptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('project_notification_id');
            $table->uuid('employee_task_request_id')->nullable();
            $table->uuid('process_id')->nullable();
            $table->uuid('procedure_setting_id')->nullable();
            $table->boolean('reasons_resolved')->default(false);
            $table->boolean('safety_notes_reviewed')->default(false);
            $table->boolean('site_ready')->default(false);
            $table->boolean('contractor_notified')->default(false);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending');
            $table->uuid('requested_by')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'project_notification_id'], 'pnwr_company_notification_idx');
            $table->index('employee_task_request_id', 'pnwr_task_request_idx');
            $table->index('process_id', 'pnwr_process_idx');
            $table->index('status', 'pnwr_status_idx');

            $table->foreign('company_id', 'pnwr_company_fk')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('project_notification_id', 'pnwr_project_notification_fk')->references('id')->on('project_notifications')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notification_work_resumptions');
    }
}
