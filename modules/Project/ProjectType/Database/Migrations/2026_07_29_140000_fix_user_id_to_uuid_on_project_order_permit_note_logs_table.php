<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_order_permit_note_logs')) {
            return;
        }

        // The production table predates the create-migration's shape: the FK on user_id
        // may be missing or named differently, so discover it instead of assuming the
        // conventional name (dropping a non-existent FK aborts the run with MySQL 1091).
        $fkName = $this->foreignKeyNameOn('project_order_permit_note_logs', 'user_id');

        Schema::table('project_order_permit_note_logs', function (Blueprint $table) use ($fkName) {
            if ($fkName !== null) {
                $table->dropForeign($fkName);
            }

            if (Schema::hasColumn('project_order_permit_note_logs', 'user_id')) {
                $table->dropColumn('user_id');
            }

            // Recreate as uuid to match users table
            $table->uuid('user_id')->nullable()->after('project_order_permit_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        $fkName = $this->foreignKeyNameOn('project_order_permit_note_logs', 'user_id');

        Schema::table('project_order_permit_note_logs', function (Blueprint $table) use ($fkName) {
            if ($fkName !== null) {
                $table->dropForeign($fkName);
            }

            if (Schema::hasColumn('project_order_permit_note_logs', 'user_id')) {
                $table->dropColumn('user_id');
            }

            $table->unsignedBigInteger('user_id')->nullable()->after('project_order_permit_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Actual FK constraint name on a column, or null when none exists.
     */
    private function foreignKeyNameOn(string $table, string $column): ?string
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->value('CONSTRAINT_NAME');
    }
};
