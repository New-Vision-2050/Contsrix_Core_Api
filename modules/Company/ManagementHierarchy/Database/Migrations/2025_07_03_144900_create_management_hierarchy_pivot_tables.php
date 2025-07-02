<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create management_hierarchy_job_types pivot table
        Schema::create('management_hierarchy_job_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_management_hierarchy_id');
            $table->uuid('job_type_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('source_management_hierarchy_id', 'mh_job_types_mh_id_foreign')
                  ->references('id')->on('source_management_hierarchies')->onDelete('cascade');
            $table->foreign('job_type_id', 'mh_job_types_jt_id_foreign')
                  ->references('id')->on('job_types')->onDelete('cascade');

            // Indexes
            $table->index(['source_management_hierarchy_id', 'job_type_id'], 'mh_job_types_composite_index');
            $table->unique(['source_management_hierarchy_id', 'job_type_id'], 'mh_job_types_unique');
        });

        // Create management_hierarchy_job_titles pivot table
        Schema::create('management_hierarchy_job_titles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_management_hierarchy_id');
            $table->uuid('job_title_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('source_management_hierarchy_id', 'mh_job_titles_mh_id_foreign')
                  ->references('id')->on('source_management_hierarchies')->onDelete('cascade');
            $table->foreign('job_title_id', 'mh_job_titles_jt_id_foreign')
                  ->references('id')->on('job_titles')->onDelete('cascade');

            // Indexes
            $table->index(['source_management_hierarchy_id', 'job_title_id'], 'mh_job_titles_composite_index');
            $table->unique(['source_management_hierarchy_id', 'job_title_id'], 'mh_job_titles_unique');
        });

        // Create management_hierarchy_branches pivot table (self-referencing)
        Schema::create('management_hierarchy_branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_management_hierarchy_id');
            $table->unsignedBigInteger('branch_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('source_management_hierarchy_id', 'mh_branches_mh_id_foreign')
                  ->references('id')->on('source_management_hierarchies')->onDelete('cascade');
            $table->foreign('branch_id', 'mh_branches_branch_id_foreign')
                  ->references('id')->on('management_hierarchies')->onDelete('cascade');

            // Indexes
            $table->index(['source_management_hierarchy_id', 'branch_id'], 'mh_branches_composite_index');
            $table->unique(['source_management_hierarchy_id', 'branch_id'], 'mh_branches_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('management_hierarchy_branches');
        Schema::dropIfExists('management_hierarchy_job_titles');
        Schema::dropIfExists('management_hierarchy_job_types');
    }
};
