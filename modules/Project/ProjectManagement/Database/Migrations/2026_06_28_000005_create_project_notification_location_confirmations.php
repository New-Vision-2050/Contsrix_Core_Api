<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectNotificationLocationConfirmations extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_notification_location_confirmations')) {
            return;
        }

        Schema::create('project_notification_location_confirmations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('project_notification_id');
            $table->uuid('employee_task_request_id')->nullable();
            $table->uuid('process_id')->nullable();
            $table->uuid('procedure_setting_id')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('distance_meters', 10, 2)->nullable();
            $table->boolean('is_inside_location')->default(false);
            $table->string('status', 30)->default('pending');
            $table->uuid('requested_by')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'project_notification_id'], 'pnlc_company_notification_idx');
            $table->index('employee_task_request_id', 'pnlc_task_request_idx');
            $table->index('process_id', 'pnlc_process_idx');
            $table->index('status', 'pnlc_status_idx');

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('project_notification_id')->references('id')->on('project_notifications')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notification_location_confirmations');
    }
}
