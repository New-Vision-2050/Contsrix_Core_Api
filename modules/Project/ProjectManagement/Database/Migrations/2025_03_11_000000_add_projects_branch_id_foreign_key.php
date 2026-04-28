<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONSTRAINT_NAME = 'projects_branch_id_foreign';

    /**
     * Runs after management_hierarchies exists (2025_03_10_*).
     */
    public function up(): void
    {
        if (! Schema::hasTable('management_hierarchies') || ! Schema::hasTable('projects')) {
            return;
        }

        if (! Schema::hasColumn('projects', 'branch_id')) {
            return;
        }

        if ($this->foreignKeyExists('projects', self::CONSTRAINT_NAME)) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('branch_id')
                ->references('id')
                ->on('management_hierarchies')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        if (! $this->foreignKeyExists('projects', self::CONSTRAINT_NAME)) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });
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
