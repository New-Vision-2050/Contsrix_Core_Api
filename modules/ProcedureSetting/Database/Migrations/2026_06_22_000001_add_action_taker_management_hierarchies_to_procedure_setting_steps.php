<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds action_taker_management_hierarchies column (text, stores JSON array)
 * to procedure_setting_steps.
 *
 * Each element: { action_taker_management_hierarchy_type: string, is_Deputy_Director: bool }
 * Replaces the deprecated action_taker_management_hierarchy_type (single) and
 * action_taker_alternative_management_hierarchy_type (array) columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        if (! Schema::hasColumn('procedure_setting_steps', 'action_taker_management_hierarchies')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->text('action_taker_management_hierarchies')
                    ->nullable()
                    ->after('action_taker_alternative_management_hierarchy_type');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        if (Schema::hasColumn('procedure_setting_steps', 'action_taker_management_hierarchies')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->dropColumn('action_taker_management_hierarchies');
            });
        }
    }
};
