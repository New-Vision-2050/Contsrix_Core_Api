<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_requirements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('project_id')->index();
            $table->string('requirement_code');
            $table->string('required_document_name');
            $table->string('document');
            $table->uuid('document_type_id')->nullable()->index();
            $table->string('document_type');
            $table->uuid('specialization_id')->nullable()->index();
            $table->string('specialization')->nullable()->index();
            $table->string('stage')->nullable()->index();
            $table->uuid('sending_entity_id')->nullable()->index();
            $table->string('sending_entity')->nullable()->index();
            $table->uuid('review_entity_id')->nullable()->index();
            $table->string('review_entity')->nullable()->index();
            $table->string('repetition');
            $table->string('repetition_interval_type')->nullable();
            $table->json('repeat_days')->nullable();
            $table->string('evaluation_status')->default('pending_acceptance')->index();
            $table->string('resulting_document')->nullable();
            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();

            $table->foreign('document_type_id')
                ->references('id')
                ->on('document_types')
                ->nullOnDelete();

            $table->foreign('specialization_id')
                ->references('id')
                ->on('academic_specializations')
                ->nullOnDelete();

            $table->foreign('sending_entity_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();

            $table->foreign('review_entity_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();

            $table->unique(['project_id', 'requirement_code'], 'project_requirements_project_code_unique');
            $table->index(['project_id', 'evaluation_status'], 'project_requirements_project_status_index');
            $table->index(['project_id', 'document_type'], 'project_requirements_project_doc_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_requirements');
    }
};
