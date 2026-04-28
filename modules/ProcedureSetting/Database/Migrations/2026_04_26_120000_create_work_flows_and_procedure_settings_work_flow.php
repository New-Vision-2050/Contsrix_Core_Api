<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PROCEDURE_SETTINGS_WORK_FLOW_FK = 'procedure_settings_work_flow_id_foreign';

    public function up(): void
    {
        Schema::create('work_flows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable()->index();
            $table->string('name')->default('default');
            $table->timestamps();
        });

        if (Schema::hasTable('companies')) {
            Schema::table('work_flows', function (Blueprint $table) {
                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('management_hierarchies')) {
            Schema::create('management_hierarchy_work_flow', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('management_hierarchy_id');
                $table->uuid('work_flow_id');
                $table->timestamps();

                $table->foreign('management_hierarchy_id')
                    ->references('id')
                    ->on('management_hierarchies')
                    ->cascadeOnDelete();

                $table->foreign('work_flow_id')
                    ->references('id')
                    ->on('work_flows')
                    ->cascadeOnDelete();

                $table->unique(
                    ['management_hierarchy_id', 'work_flow_id'],
                    'mh_work_flow_mh_id_work_flow_id_unique'
                );
            });
        }

        if (! Schema::hasTable('procedure_settings')) {
            return;
        }

        Schema::table('procedure_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('procedure_settings', 'work_flow_id')) {
                $table->uuid('work_flow_id')->nullable()->after('company_id')->index();
            }
        });

        if ($this->foreignKeyExists('procedure_settings', self::PROCEDURE_SETTINGS_WORK_FLOW_FK)) {
            return;
        }

        if (! Schema::hasColumn('procedure_settings', 'work_flow_id')) {
            return;
        }

        Schema::table('procedure_settings', function (Blueprint $table) {
            $table->foreign('work_flow_id')
                ->references('id')
                ->on('work_flows')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('procedure_settings')) {
            if ($this->foreignKeyExists('procedure_settings', self::PROCEDURE_SETTINGS_WORK_FLOW_FK)) {
                Schema::table('procedure_settings', function (Blueprint $table) {
                    $table->dropForeign(['work_flow_id']);
                });
            }
            if (Schema::hasColumn('procedure_settings', 'work_flow_id')) {
                Schema::table('procedure_settings', function (Blueprint $table) {
                    $table->dropColumn('work_flow_id');
                });
            }
        }

        Schema::dropIfExists('management_hierarchy_work_flow');
        Schema::dropIfExists('work_flows');
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $result = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND CONSTRAINT_NAME = ?
             AND CONSTRAINT_TYPE = ?',
            [$table, $constraintName, 'FOREIGN KEY']
        );

        return count($result) > 0;
    }
};
