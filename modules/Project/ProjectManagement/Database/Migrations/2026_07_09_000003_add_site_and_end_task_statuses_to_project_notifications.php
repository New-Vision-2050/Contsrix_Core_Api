<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSiteAndEndTaskStatusesToProjectNotifications extends Migration
{
    public function up(): void
    {
        Schema::table('project_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('project_notifications', 'update_site_status_id')) {
                $table->uuid('update_site_status_id')->nullable()->after('status');
                $table->foreign('update_site_status_id')
                    ->references('id')
                    ->on('project_notification_update_site_statuses')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('project_notifications', 'end_task_status_id')) {
                $table->uuid('end_task_status_id')->nullable()->after('update_site_status_id');
                $table->foreign('end_task_status_id')
                    ->references('id')
                    ->on('project_notification_end_task_statuses')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_notifications', function (Blueprint $table) {
            $table->dropForeign(['update_site_status_id']);
            $table->dropColumn('update_site_status_id');
            $table->dropForeign(['end_task_status_id']);
            $table->dropColumn('end_task_status_id');
        });
    }
}
