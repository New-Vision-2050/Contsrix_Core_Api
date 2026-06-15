<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employee_task_requests', 'internal_process_type_id')) {
            return;
        }

        Schema::table('employee_task_requests', function (Blueprint $table) {
            $table->uuid('internal_process_type_id')->nullable()->after('project_id');
            $table->foreign('internal_process_type_id', 'etr_internal_process_type_fk')
                ->references('id')
                ->on('internal_process_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('employee_task_requests', 'internal_process_type_id')) {
            return;
        }

        Schema::table('employee_task_requests', function (Blueprint $table) {
            $table->dropForeign('etr_internal_process_type_fk');
            $table->dropColumn('internal_process_type_id');
        });
    }
};
