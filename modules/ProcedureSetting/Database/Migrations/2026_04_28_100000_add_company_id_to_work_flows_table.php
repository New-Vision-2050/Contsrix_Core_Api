<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * For databases that ran work_flows creation before {@see company_id} existed on {@see work_flows}.
 */
return new class extends Migration
{
    private const FK_NAME = 'work_flows_company_id_foreign';

    public function up(): void
    {
        if (! Schema::hasTable('work_flows') || Schema::hasColumn('work_flows', 'company_id')) {
            return;
        }

        Schema::table('work_flows', function (Blueprint $table) {
            $table->uuid('company_id')->nullable()->after('id')->index();
        });

        $this->backfillCompanyIdFromLegacyName();

        if (Schema::hasTable('companies') && ! $this->foreignKeyExists('work_flows', self::FK_NAME)) {
            Schema::table('work_flows', function (Blueprint $table) {
                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('work_flows') || ! Schema::hasColumn('work_flows', 'company_id')) {
            return;
        }

        if ($this->foreignKeyExists('work_flows', self::FK_NAME)) {
            Schema::table('work_flows', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });
        }

        Schema::table('work_flows', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }

    private function backfillCompanyIdFromLegacyName(): void
    {
        $rows = DB::table('work_flows')->whereNull('company_id')->get(['id', 'name']);

        foreach ($rows as $row) {
            if (! is_string($row->name) || ! str_starts_with($row->name, 'default_')) {
                continue;
            }

            $candidate = substr($row->name, strlen('default_'));
            if (strlen($candidate) !== 36) {
                continue;
            }

            DB::table('work_flows')->where('id', $row->id)->update([
                'company_id' => $candidate,
                'name'       => 'default',
            ]);
        }
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
