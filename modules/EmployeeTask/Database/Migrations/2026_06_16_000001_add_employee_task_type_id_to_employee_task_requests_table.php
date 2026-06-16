<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {   if (!Schema::hasTable('employee_task_requests')) {
            return;
        }
        Schema::table('employee_task_requests', function (Blueprint $table) {
            $table->uuid('employee_task_type_id')->nullable()->after('project_id');
            $table->foreign('employee_task_type_id')
                ->references('id')->on('employee_task_types')
                ->nullOnDelete();


            if (!Schema::hasColumn('employee_task_requests', 'item_type')) {
                $table->string('item_type')->nullable()->after('project_id');
            }
            if (!Schema::hasColumn('employee_task_requests', 'item_id')) {
                $table->uuid('item_id')->nullable()->after('item_type');
            }

            $table->index(['item_type', 'item_id'], 'employee_task_requests_item_idx');
        });

    }







    public function down(): void
    {
        Schema::table('employee_task_requests', function (Blueprint $table) {
            $table->dropForeign(['employee_task_type_id']);
            $table->dropColumn('employee_task_type_id');
            $table->dropColumn('item_type');
            $table->dropColumn('item_id');
        });
    }
};
