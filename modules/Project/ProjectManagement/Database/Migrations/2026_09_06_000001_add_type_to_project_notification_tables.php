<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_notifications') && ! Schema::hasColumn('project_notifications', 'type')) {
            Schema::table('project_notifications', function (Blueprint $table) {
                $table->enum('type', ['electricity', 'water'])->default('electricity')->after('notification_type');
            });
        }

        if (Schema::hasTable('project_notification_types') && ! Schema::hasColumn('project_notification_types', 'type')) {
            Schema::table('project_notification_types', function (Blueprint $table) {
                $table->enum('type', ['electricity', 'water'])->default('electricity')->after('name_en');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_notifications') && Schema::hasColumn('project_notifications', 'type')) {
            Schema::table('project_notifications', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }

        if (Schema::hasTable('project_notification_types') && Schema::hasColumn('project_notification_types', 'type')) {
            Schema::table('project_notification_types', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
