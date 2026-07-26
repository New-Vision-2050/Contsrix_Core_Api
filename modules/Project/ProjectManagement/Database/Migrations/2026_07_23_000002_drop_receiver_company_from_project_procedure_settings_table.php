<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_procedure_settings')
            || ! Schema::hasColumn('project_procedure_settings', 'receiver_company_id')) {
            return;
        }

        Schema::table('project_procedure_settings', function (Blueprint $table): void {
            $table->dropForeign(['receiver_company_id']);
            $table->dropIndex(['receiver_company_id']);
            $table->dropColumn('receiver_company_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_procedure_settings')
            || Schema::hasColumn('project_procedure_settings', 'receiver_company_id')) {
            return;
        }

        Schema::table('project_procedure_settings', function (Blueprint $table): void {
            $table->uuid('receiver_company_id')->nullable()->after('procedure_setting_id')->index();

            $table->foreign('receiver_company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();
        });
    }
};
