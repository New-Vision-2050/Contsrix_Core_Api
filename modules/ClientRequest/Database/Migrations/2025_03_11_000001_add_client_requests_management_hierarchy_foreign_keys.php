<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BRANCH_FK = 'client_requests_branch_id_foreign';

    private const MANAGEMENT_FK = 'client_requests_management_id_foreign';

    /**
     * Runs after management_hierarchies exists (2025_03_10_*).
     */
    public function up(): void
    {
        if (! Schema::hasTable('management_hierarchies') || ! Schema::hasTable('client_requests')) {
            return;
        }

        if (Schema::hasColumn('client_requests', 'branch_id')
            && ! $this->foreignKeyExists('client_requests', self::BRANCH_FK)) {
            Schema::table('client_requests', function (Blueprint $table) {
                $table->foreign('branch_id')
                    ->references('id')
                    ->on('management_hierarchies')
                    ->onDelete('set null');
            });
        }

        if (Schema::hasColumn('client_requests', 'management_id')
            && ! $this->foreignKeyExists('client_requests', self::MANAGEMENT_FK)) {
            Schema::table('client_requests', function (Blueprint $table) {
                $table->foreign('management_id')
                    ->references('id')
                    ->on('management_hierarchies')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_requests')) {
            return;
        }

        Schema::table('client_requests', function (Blueprint $table) {
            if ($this->foreignKeyExists('client_requests', self::BRANCH_FK)) {
                $table->dropForeign(['branch_id']);
            }
            if ($this->foreignKeyExists('client_requests', self::MANAGEMENT_FK)) {
                $table->dropForeign(['management_id']);
            }
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
