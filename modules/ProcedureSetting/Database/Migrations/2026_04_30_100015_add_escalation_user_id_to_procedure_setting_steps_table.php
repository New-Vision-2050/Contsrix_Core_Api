<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PS_STEPS_ESCAL_USER_FK = 'ps_steps_esc_user_fk';

    public function up(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        if (! Schema::hasColumn('procedure_setting_steps', 'escalation_user_id')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->uuid('escalation_user_id')->nullable()->after('company_id');
            });
        }

        if (Schema::hasTable('users')) {
            try {
                Schema::table('procedure_setting_steps', function (Blueprint $table) {
                    $table->foreign('escalation_user_id', self::PS_STEPS_ESCAL_USER_FK)
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                });
            } catch (\Throwable) {
                // FK already present or unsupported driver
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        try {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->dropForeign(self::PS_STEPS_ESCAL_USER_FK);
            });
        } catch (\Throwable) {
            try {
                Schema::table('procedure_setting_steps', function (Blueprint $table) {
                    $table->dropForeign(['escalation_user_id']);
                });
            } catch (\Throwable) {
                //
            }
        }

        if (Schema::hasColumn('procedure_setting_steps', 'escalation_user_id')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->dropColumn('escalation_user_id');
            });
        }
    }
};
