<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectTypeIdToProjectNotificationSiteStatusTypesTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('project_notification_site_status_types', 'project_type_id')) {
            return;
        }

        Schema::table('project_notification_site_status_types', function (Blueprint $table) {
            $table->unsignedBigInteger('project_type_id')->nullable()->after('id')->index();

            $table->foreign('project_type_id')
                ->references('id')
                ->on('project_types')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('project_notification_site_status_types', function (Blueprint $table) {
            if (Schema::hasColumn('project_notification_site_status_types', 'project_type_id')) {
                $table->dropForeign(['project_type_id']);
                $table->dropColumn('project_type_id');
            }
        });
    }
}
