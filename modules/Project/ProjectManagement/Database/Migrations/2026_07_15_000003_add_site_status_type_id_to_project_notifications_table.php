<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSiteStatusTypeIdToProjectNotificationsTable extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_notifications')) {
            return;
        }

        Schema::table('project_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('project_notifications', 'site_status_type_id')) {
                $table->uuid('site_status_type_id')->nullable()->after('notes');
                $table->index('site_status_type_id', 'pn_site_status_type_idx');
                $table->foreign('site_status_type_id', 'pn_site_status_type_fk')
                    ->references('id')
                    ->on('project_notification_site_status_types')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_notifications')) {
            return;
        }

        Schema::table('project_notifications', function (Blueprint $table) {
            $table->dropForeign(['site_status_type_id']);
            $table->dropColumn('site_status_type_id');
        });
    }
}
