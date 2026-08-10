<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_procedure_setting_receiver_companies')) {
            return;
        }

        Schema::create('project_procedure_setting_receiver_companies', function (Blueprint $table): void {
            $table->uuid('project_procedure_setting_id');
            $table->uuid('company_id');
            $table->timestamps();

            $table->primary(
                ['project_procedure_setting_id', 'company_id'],
                'project_proc_receiver_companies_primary'
            );

            $table->foreign(
                'project_procedure_setting_id',
                'project_proc_receiver_proc_fk'
            )
                ->references('id')
                ->on('project_procedure_settings')
                ->cascadeOnDelete();

            $table->foreign('company_id', 'project_proc_receiver_company_fk')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->index('company_id', 'project_proc_receiver_company_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_procedure_setting_receiver_companies');
    }
};
