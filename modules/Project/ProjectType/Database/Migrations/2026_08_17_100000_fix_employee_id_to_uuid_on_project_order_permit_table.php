<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_order_permit')) {
            return;
        }

        if (!Schema::hasColumn('project_order_permit', 'employee_id')) {
            // Column doesn't exist at all — let the original migration create it as UUID.
            return;
        }

        // The production table predates the UUID migration: employee_id was added
        // as an integer, so the hasColumn guard in the original migration skipped
        // the UUID creation. Discover and drop the existing FK, drop the column,
        // then recreate as UUID.
        $fkName = $this->foreignKeyNameOn('project_order_permit', 'employee_id');

        Schema::table('project_order_permit', function (Blueprint $table) use ($fkName) {
            if ($fkName !== null) {
                $table->dropForeign($fkName);
            }

            $table->dropColumn('employee_id');
        });

        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->uuid('employee_id')->nullable()->after('consultant_price');

            $table->foreign('employee_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('project_order_permit', 'employee_id')) {
            return;
        }

        $fkName = $this->foreignKeyNameOn('project_order_permit', 'employee_id');

        Schema::table('project_order_permit', function (Blueprint $table) use ($fkName) {
            if ($fkName !== null) {
                $table->dropForeign($fkName);
            }

            $table->dropColumn('employee_id');
        });

        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('consultant_price');

            $table->foreign('employee_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
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
