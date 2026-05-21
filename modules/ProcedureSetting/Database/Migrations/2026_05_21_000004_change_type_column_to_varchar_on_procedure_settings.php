<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The `procedure_settings.type` column was originally created as an ENUM on
 * existing databases (pre-dating the 2026_04_27 migration which skips when
 * the table already exists).  Adding `employee_task_request` to the PHP enum
 * alone is not enough — MySQL truncates/rejects values not in the ENUM list.
 *
 * This migration safely converts the column to VARCHAR(255) so that any
 * current or future ProcedureSettingType value is accepted without requiring
 * another schema change.
 */
return new class extends Migration
{
    public function up(): void
    {
        $columnType = $this->getColumnType();

        if ($columnType === 'varchar') {
            return;
        }

        Schema::table('procedure_settings', function (Blueprint $table) {
            $table->string('type', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('procedure_settings', function (Blueprint $table) {
            $table->enum('type', ['client_request', 'price_offer', 'contract', 'employee_task_request'])->change();
        });
    }

    private function getColumnType(): string
    {
        $result = DB::select(
            "SELECT DATA_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'procedure_settings'
               AND COLUMN_NAME  = 'type'
             LIMIT 1"
        );

        return strtolower($result[0]->DATA_TYPE ?? '');
    }
};
