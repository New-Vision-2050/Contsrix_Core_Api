<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaskTimeToProjectNotifications extends Migration
{
    public function up(): void
    {
        Schema::table('project_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('project_notifications', 'task_time')) {
                $table->time('task_time')->nullable()->after('task_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('project_notifications', 'task_time')) {
                $table->dropColumn('task_time');
            }
        });
    }
}
