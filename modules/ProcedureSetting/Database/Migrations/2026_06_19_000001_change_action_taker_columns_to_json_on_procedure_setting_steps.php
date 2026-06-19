<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Changes:
 * - action_taker_alternative_management_hierarchy_type → text (stores JSON array of types)
 * - action_taker_specific_procedure_type              → text (stores JSON array of types)
 * - action_taker_specific_procedure_id               → text (stores JSON array of ids)
 *
 * These columns now store JSON-encoded arrays so multiple alternatives /
 * multiple specific-procedure targets can be configured per step.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        Schema::table('procedure_setting_steps', function (Blueprint $table) {
            if (Schema::hasColumn('procedure_setting_steps', 'action_taker_alternative_management_hierarchy_type')) {
                $table->text('action_taker_alternative_management_hierarchy_type')->nullable()->change();
            }

            if (Schema::hasColumn('procedure_setting_steps', 'action_taker_specific_procedure_type')) {
                $table->text('action_taker_specific_procedure_type')->nullable()->change();
            }

            if (Schema::hasColumn('procedure_setting_steps', 'action_taker_specific_procedure_id')) {
                $table->text('action_taker_specific_procedure_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        Schema::table('procedure_setting_steps', function (Blueprint $table) {
            if (Schema::hasColumn('procedure_setting_steps', 'action_taker_alternative_management_hierarchy_type')) {
                $table->string('action_taker_alternative_management_hierarchy_type', 30)->nullable()->change();
            }

            if (Schema::hasColumn('procedure_setting_steps', 'action_taker_specific_procedure_type')) {
                $table->string('action_taker_specific_procedure_type', 30)->nullable()->change();
            }

            if (Schema::hasColumn('procedure_setting_steps', 'action_taker_specific_procedure_id')) {
                $table->string('action_taker_specific_procedure_id')->nullable()->change();
            }
        });
    }
};
