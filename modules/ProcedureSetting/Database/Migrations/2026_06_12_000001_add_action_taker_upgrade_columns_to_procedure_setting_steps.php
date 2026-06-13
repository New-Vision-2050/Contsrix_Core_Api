<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        if (! Schema::hasColumn('procedure_setting_steps', 'action_taker_alternative_management_hierarchy_type')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->string('action_taker_alternative_management_hierarchy_type', 30)
                    ->nullable()
                    ->after('action_taker_management_hierarchy_type');
            });
        }

        if (! Schema::hasColumn('procedure_setting_steps', 'action_taker_specific_procedure_type')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->string('action_taker_specific_procedure_type', 30)
                    ->nullable()
                    ->after('action_taker_alternative_management_hierarchy_type');
            });
        }

        if (! Schema::hasColumn('procedure_setting_steps', 'action_taker_specific_procedure_id')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->string('action_taker_specific_procedure_id')
                    ->nullable()
                    ->after('action_taker_specific_procedure_type');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        if (Schema::hasColumn('procedure_setting_steps', 'action_taker_specific_procedure_id')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->dropColumn('action_taker_specific_procedure_id');
            });
        }

        if (Schema::hasColumn('procedure_setting_steps', 'action_taker_specific_procedure_type')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->dropColumn('action_taker_specific_procedure_type');
            });
        }

        if (Schema::hasColumn('procedure_setting_steps', 'action_taker_alternative_management_hierarchy_type')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->dropColumn('action_taker_alternative_management_hierarchy_type');
            });
        }
    }
};
