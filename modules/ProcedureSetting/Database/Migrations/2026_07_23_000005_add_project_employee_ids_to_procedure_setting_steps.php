<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        if (! Schema::hasColumn('procedure_setting_steps', 'project_employee_ids')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table): void {
                $table->text('project_employee_ids')
                    ->nullable()
                    ->after('receiver_company_ids');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        if (Schema::hasColumn('procedure_setting_steps', 'project_employee_ids')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table): void {
                $table->dropColumn('project_employee_ids');
            });
        }
    }
};
