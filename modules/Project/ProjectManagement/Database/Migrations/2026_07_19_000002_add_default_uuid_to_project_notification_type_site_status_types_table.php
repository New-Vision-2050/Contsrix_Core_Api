<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDefaultUuidToProjectNotificationTypeSiteStatusTypesTable extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_notification_type_site_status_types')) {
            return;
        }

        DB::statement(
            "ALTER TABLE project_notification_type_site_status_types "
            ."MODIFY COLUMN id CHAR(36) NOT NULL DEFAULT (UUID())"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE project_notification_type_site_status_types "
            ."MODIFY COLUMN id CHAR(36) NOT NULL"
        );
    }
}
