<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_requirement_submissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->index();
            $table->uuid('project_requirement_id')->index();
            $table->timestamps();

            $table->foreign('project_id', 'project_req_submissions_project_fk')
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();

            $table->foreign('project_requirement_id', 'project_req_submissions_requirement_fk')
                ->references('id')
                ->on('project_requirements')
                ->cascadeOnDelete();

            $table->index(
                ['project_requirement_id', 'created_at'],
                'project_req_submissions_latest_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_requirement_submissions');
    }
};
