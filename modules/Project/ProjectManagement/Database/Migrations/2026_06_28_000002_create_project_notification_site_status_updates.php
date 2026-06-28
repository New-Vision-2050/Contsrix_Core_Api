<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectNotificationSiteStatusUpdates extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_notification_site_status_updates')) {
            return;
        }

        Schema::create('project_notification_site_status_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('project_notification_id');
            $table->uuid('employee_task_request_id')->nullable();
            $table->uuid('process_id')->nullable();
            $table->uuid('procedure_setting_id')->nullable();
            $table->date('update_date')->nullable();
            $table->time('update_time')->nullable();
            $table->uuid('site_status_id')->nullable();
            $table->uuid('current_site_status_id')->nullable();
            $table->string('work_stages_completed')->nullable();
            $table->text('current_status_description')->nullable();
            $table->decimal('completion_percentage', 5, 2)->nullable();
            $table->text('updates_obstacles')->nullable();
            $table->text('additional_notes')->nullable();
            $table->string('status', 30)->default('pending');
            $table->uuid('requested_by')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'project_notification_id'], 'pnsu_company_notification_idx');
            $table->index('employee_task_request_id', 'pnsu_task_request_idx');
            $table->index('process_id', 'pnsu_process_idx');
            $table->index('site_status_id', 'pnsu_site_status_idx');
            $table->index('current_site_status_id', 'pnsu_current_site_status_idx');
            $table->index('status', 'pnsu_status_idx');

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('project_notification_id')->references('id')->on('project_notifications')->cascadeOnDelete();
            $table->foreign('site_status_id')->references('id')->on('project_notification_site_statuses')->nullOnDelete();
            $table->foreign('current_site_status_id')->references('id')->on('project_notification_site_statuses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notification_site_status_updates');
    }
}
