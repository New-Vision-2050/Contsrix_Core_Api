<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_procedure_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('project_id')->index();
            $table->uuid('procedure_setting_id')->index();
            $table->uuid('receiver_company_id')->nullable()->index();
            $table->uuid('attachment_type_id')->nullable()->index();
            $table->uuid('attachment_sub_type_id')->nullable()->index();
            $table->uuid('attachment_sub_sub_type_id')->nullable()->index();
            $table->uuid('job_attribute_id')->nullable()->index();
            $table->boolean('used_in_document_cycle')->default(false);
            $table->boolean('appears_in_archive_after_approval')->default(false);
            $table->boolean('appears_in_attachments_library')->default(false);
            $table->boolean('requires_asset_id')->default(false);
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();

            $table->foreign('procedure_setting_id')
                ->references('id')
                ->on('procedure_settings')
                ->cascadeOnDelete();

            $table->foreign('receiver_company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();

            $table->foreign('attachment_type_id')
                ->references('id')
                ->on('folders')
                ->nullOnDelete();

            $table->foreign('attachment_sub_type_id')
                ->references('id')
                ->on('folders')
                ->nullOnDelete();

            $table->foreign('attachment_sub_sub_type_id')
                ->references('id')
                ->on('folders')
                ->nullOnDelete();

            $table->foreign('job_attribute_id')
                ->references('id')
                ->on('project_procedure_job_attributes')
                ->nullOnDelete();

            $table->unique(
                ['project_id', 'procedure_setting_id'],
                'project_proc_settings_project_proc_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_procedure_settings');
    }
};
