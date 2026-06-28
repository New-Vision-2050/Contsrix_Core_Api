<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectNotificationWorkStoppageReportReasons extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_notification_work_stoppage_report_reasons')) {
            return;
        }

        Schema::create('project_notification_work_stoppage_report_reasons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_notification_work_stoppage_report_id');
            $table->uuid('work_stoppage_reason_id')->nullable();
            $table->string('reason_name_ar')->nullable();
            $table->string('reason_name_en')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('project_notification_work_stoppage_report_id', 'pnwsrr_report_idx');
            $table->index('work_stoppage_reason_id', 'pnwsrr_reason_idx');
            $table->index('sort_order', 'pnwsrr_sort_idx');

            $table->foreign('project_notification_work_stoppage_report_id')
                ->references('id')
                ->on('project_notification_work_stoppage_reports')
                ->cascadeOnDelete();
            $table->foreign('work_stoppage_reason_id')
                ->references('id')
                ->on('project_notification_work_stoppage_reasons')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notification_work_stoppage_report_reasons');
    }
}
