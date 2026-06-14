<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employee_task_extension_requests', 'procedure_setting_id')) {
            Schema::table('employee_task_extension_requests', function (Blueprint $table) {
                $table->uuid('procedure_setting_id')->nullable()->after('company_id');
                $table->foreign('procedure_setting_id', 'eter_procedure_setting_fk')
                    ->references('id')
                    ->on('procedure_settings')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('employee_task_approval_requests', 'procedure_setting_id')) {
            Schema::table('employee_task_approval_requests', function (Blueprint $table) {
                $table->uuid('procedure_setting_id')->nullable()->after('company_id');
                $table->foreign('procedure_setting_id', 'etar_procedure_setting_fk')
                    ->references('id')
                    ->on('procedure_settings')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employee_task_extension_requests', 'procedure_setting_id')) {
            Schema::table('employee_task_extension_requests', function (Blueprint $table) {
                $table->dropForeign('eter_procedure_setting_fk');
                $table->dropColumn('procedure_setting_id');
            });
        }

        if (Schema::hasColumn('employee_task_approval_requests', 'procedure_setting_id')) {
            Schema::table('employee_task_approval_requests', function (Blueprint $table) {
                $table->dropForeign('etar_procedure_setting_fk');
                $table->dropColumn('procedure_setting_id');
            });
        }
    }
};
