<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const WORK_FLOWS_PROJECT_FK = 'work_flows_project_fk';

    public function up(): void
    {
        if (! Schema::hasTable('work_flows')) {
            return;
        }

        Schema::table('work_flows', function (Blueprint $table): void {
            if (! Schema::hasColumn('work_flows', 'project_id')) {
                $table->uuid('project_id')->nullable()->after('company_id')->index();
            }
        });

        if (! Schema::hasTable('projects') || $this->foreignKeyExists('work_flows', self::WORK_FLOWS_PROJECT_FK)) {
            return;
        }

        Schema::table('work_flows', function (Blueprint $table): void {
            $table->foreign('project_id', self::WORK_FLOWS_PROJECT_FK)
                ->references('id')
                ->on('projects')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('work_flows') || ! Schema::hasColumn('work_flows', 'project_id')) {
            return;
        }

        if ($this->foreignKeyExists('work_flows', self::WORK_FLOWS_PROJECT_FK)) {
            Schema::table('work_flows', function (Blueprint $table): void {
                $table->dropForeign(self::WORK_FLOWS_PROJECT_FK);
            });
        }

        Schema::table('work_flows', function (Blueprint $table): void {
            $table->dropColumn('project_id');
        });
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        try {
            $result = DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                 AND TABLE_NAME = ?
                 AND CONSTRAINT_NAME = ?
                 AND CONSTRAINT_TYPE = ?',
                [$table, $constraintName, 'FOREIGN KEY']
            );
        } catch (Throwable) {
            return false;
        }

        return count($result) > 0;
    }
};
