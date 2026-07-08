<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastSiteStatusReminderSentAtToProjectNotifications extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('project_notifications', 'last_site_status_reminder_sent_at')) {
            return;
        }

        Schema::table('project_notifications', function (Blueprint $table) {
            $table->dateTime('last_site_status_reminder_sent_at')->nullable()->after('location_confirmed_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('project_notifications', 'last_site_status_reminder_sent_at')) {
            return;
        }

        Schema::table('project_notifications', function (Blueprint $table) {
            $table->dropColumn('last_site_status_reminder_sent_at');
        });
    }
}
