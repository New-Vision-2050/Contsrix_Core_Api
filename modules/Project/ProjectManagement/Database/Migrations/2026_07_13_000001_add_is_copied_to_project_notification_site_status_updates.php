<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsCopiedToProjectNotificationSiteStatusUpdates extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('project_notification_site_status_updates', 'is_copied')) {
            Schema::table('project_notification_site_status_updates', function (Blueprint $table) {
                $table->boolean('is_copied')->default(false)->after('status');
                $table->index('is_copied', 'pnsu_is_copied_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('project_notification_site_status_updates', 'is_copied')) {
            Schema::table('project_notification_site_status_updates', function (Blueprint $table) {
                $table->dropIndex('pnsu_is_copied_idx');
                $table->dropColumn('is_copied');
            });
        }
    }
}
