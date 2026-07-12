<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contractors')) {
            return;
        }

        Schema::table('contractors', function (Blueprint $table) {
            if (!Schema::hasColumn('contractors', 'project_id')) {
                $table->uuid('project_id')->nullable();
            }
            if (!Schema::hasColumn('contractors', 'tax_card')) {
                $table->string('tax_card')->nullable();
            }
            if (!Schema::hasColumn('contractors', 'commercial_register')) {
                $table->string('commercial_register')->nullable();
            }
            if (!Schema::hasColumn('contractors', 'activity')) {
                $table->string('activity')->nullable();
            }
            if (!Schema::hasColumn('contractors', 'email')) {
                $table->string('email')->nullable();
            }
            if (!Schema::hasColumn('contractors', 'country_id')) {
                $table->uuid('country_id')->nullable();
            }
            if (!Schema::hasColumn('contractors', 'project_contractor_id')) {
                $table->string('project_contractor_id')->nullable();
            }
            if (!Schema::hasColumn('contractors', 'project_manager_name')) {
                $table->string('project_manager_name')->nullable();
            }
            if (!Schema::hasColumn('contractors', 'project_manager_phone')) {
                $table->string('project_manager_phone')->nullable();
            }
            if (!Schema::hasColumn('contractors', 'project_manager_nationality')) {
                $table->string('project_manager_nationality')->nullable();
            }
            if (!Schema::hasColumn('contractors', 'project_manager_email')) {
                $table->string('project_manager_email')->nullable();
            }
            if (!Schema::hasColumn('contractors', 'logo')) {
                $table->string('logo')->nullable();

            }
        });

        if (Schema::hasColumn('contractors', 'project_id')) {
            try {
                Schema::table('contractors', function (Blueprint $table) {
                    $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
                });
            } catch (\Throwable $e) {
            }
        }

        if (Schema::hasColumn('contractors', 'country_id')) {
            try {
                Schema::table('contractors', function (Blueprint $table) {
                    $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
                });
            } catch (\Throwable $e) {
            }
        }

        try {
            Schema::table('contractors', function (Blueprint $table) {
                $table->unique(['project_id', 'project_contractor_id'], 'contractors_project_reference_unique');
            });
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['country_id']);
            $table->dropUnique('contractors_project_reference_unique');
        });
    }
};
