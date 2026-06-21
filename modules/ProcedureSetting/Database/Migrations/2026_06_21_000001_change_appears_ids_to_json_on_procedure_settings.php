<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Changes appears_before_id and appears_after_id from single uuid columns to
 * JSON arrays, allowing a procedure to depend on / precede multiple others.
 *
 * Steps:
 *  1. Drop the FK constraints (JSON columns cannot have FK constraints).
 *  2. Convert any existing single-UUID string values to single-element JSON arrays.
 *  3. Change the column type to JSON (nullable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedure_settings', function (\Illuminate\Database\Schema\Blueprint $table) {
            foreach (['ps_appears_before_fk', 'ps_appears_after_fk'] as $fk) {
                try {
                    $table->dropForeign($fk);
                } catch (\Throwable) {
                    // FK may not exist in all environments
                }
            }
        });

        // Convert existing single-UUID strings to single-element JSON arrays.
        // Rows that are already valid JSON arrays or NULL are left untouched.
        foreach (['appears_before_id', 'appears_after_id'] as $col) {
            DB::statement("
                UPDATE procedure_settings
                SET {$col} = JSON_ARRAY({$col})
                WHERE {$col} IS NOT NULL
                  AND JSON_VALID({$col}) = 0
            ");
        }

        Schema::table('procedure_settings', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->json('appears_before_id')->nullable()->change();
            $table->json('appears_after_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('procedure_settings', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('appears_before_id', 36)->nullable()->change();
            $table->string('appears_after_id', 36)->nullable()->change();
        });

        // Collapse single-element arrays back to plain UUID strings on rollback.
        foreach (['appears_before_id', 'appears_after_id'] as $col) {
            DB::statement("
                UPDATE procedure_settings
                SET {$col} = JSON_UNQUOTE(JSON_EXTRACT({$col}, '$[0]'))
                WHERE {$col} IS NOT NULL
                  AND JSON_LENGTH({$col}) = 1
            ");
        }

        Schema::table('procedure_settings', function (\Illuminate\Database\Schema\Blueprint $table) {
            try {
                $table->foreign('appears_before_id', 'ps_appears_before_fk')
                    ->references('id')->on('procedure_settings')->nullOnDelete();
            } catch (\Throwable) {}

            try {
                $table->foreign('appears_after_id', 'ps_appears_after_fk')
                    ->references('id')->on('procedure_settings')->nullOnDelete();
            } catch (\Throwable) {}
        });
    }
};
