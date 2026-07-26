<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_requirements')) {
            return;
        }

        if (Schema::hasColumn('project_requirements', 'document_type_id')) {
            $this->dropForeignIfExists('project_requirements', 'document_type_id');
            $this->dropIndexIfExists('project_requirements', 'document_type_id');

            Schema::table('project_requirements', function (Blueprint $table): void {
                $table->renameColumn('document_type_id', 'procedure_setting_id');
            });
        }

        if (Schema::hasColumn('project_requirements', 'procedure_setting_id')) {
            DB::table('project_requirements')->update(['procedure_setting_id' => null]);

            $this->dropIndexIfExists('project_requirements', 'procedure_setting_id');

            Schema::table('project_requirements', function (Blueprint $table): void {
                $table->index('procedure_setting_id', 'project_requirements_procedure_setting_id_index');
            });

            if (Schema::hasTable('procedure_settings')) {
                Schema::table('project_requirements', function (Blueprint $table): void {
                    $table->foreign('procedure_setting_id')
                        ->references('id')
                        ->on('procedure_settings')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_requirements')) {
            return;
        }

        if (Schema::hasColumn('project_requirements', 'procedure_setting_id')) {
            $this->dropForeignIfExists('project_requirements', 'procedure_setting_id');
            $this->dropIndexIfExists('project_requirements', 'procedure_setting_id');

            Schema::table('project_requirements', function (Blueprint $table): void {
                $table->renameColumn('procedure_setting_id', 'document_type_id');
            });
        }

        if (Schema::hasColumn('project_requirements', 'document_type_id')) {
            DB::table('project_requirements')->update(['document_type_id' => null]);

            $this->dropIndexIfExists('project_requirements', 'document_type_id');

            Schema::table('project_requirements', function (Blueprint $table): void {
                $table->index('document_type_id', 'project_requirements_document_type_id_index');
            });

            if (Schema::hasTable('document_types')) {
                Schema::table('project_requirements', function (Blueprint $table): void {
                    $table->foreign('document_type_id')
                        ->references('id')
                        ->on('document_types')
                        ->nullOnDelete();
                });
            }
        }
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($column): void {
                $table->dropForeign([$column]);
            });
        } catch (Throwable) {
        }
    }

    private function dropIndexIfExists(string $table, string $column): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($column): void {
                $table->dropIndex([$column]);
            });
        } catch (Throwable) {
        }
    }
};
