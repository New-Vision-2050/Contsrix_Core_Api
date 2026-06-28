<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaskTimeToEmployeeTaskRequests extends Migration
{
    public function up(): void
    {
        Schema::table('employee_task_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_task_requests', 'task_time')) {
                $table->time('task_time')->nullable()->after('task_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_task_requests', function (Blueprint $table) {
            if (Schema::hasColumn('employee_task_requests', 'task_time')) {
                $table->dropColumn('task_time');
            }
        });
    }
}
