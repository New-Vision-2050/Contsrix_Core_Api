<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_task_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('current_procedure_step_id')
                ->nullable()
                ->after('procedure_setting_id')
                ->comment('Tracks which ProcedureSettingStep is currently awaiting approval');
        });
    }

    public function down(): void
    {
        Schema::table('employee_task_requests', function (Blueprint $table) {
            $table->dropColumn('current_procedure_step_id');
        });
    }
};
