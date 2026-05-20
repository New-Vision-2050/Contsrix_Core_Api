<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_task_extension_requests', function (Blueprint $table) {
            $table->string('procedure_setting_id')->nullable()->after('company_id');
            $table->integer('current_procedure_step_id')->nullable()->after('procedure_setting_id');

            $table->foreign('procedure_setting_id')
                ->references('id')
                ->on('procedure_settings')
                ->onDelete('set null');

            $table->foreign('current_procedure_step_id')
                ->references('id')
                ->on('procedure_setting_steps')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('employee_task_extension_requests', function (Blueprint $table) {
            $table->dropForeignIdFor('current_procedure_step_id');
            $table->dropForeignIdFor('procedure_setting_id');
            $table->dropColumn(['procedure_setting_id', 'current_procedure_step_id']);
        });
    }
};
