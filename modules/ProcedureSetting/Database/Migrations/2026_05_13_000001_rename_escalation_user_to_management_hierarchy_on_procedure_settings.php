<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_FK = 'ps_settings_esc_user_fk';

    private const NEW_FK = 'ps_settings_esc_mh_fk';

    public function up(): void
    {
        if (! Schema::hasTable('procedure_settings')) {
            return;
        }

        // Drop old FK by constraint name
        try {
            Schema::table('procedure_settings', function (Blueprint $table) {
                $table->dropForeign(self::OLD_FK);
            });
        } catch (\Throwable) {
            try {
                Schema::table('procedure_settings', function (Blueprint $table) {
                    $table->dropForeign(['escalation_user_id']);
                });
            } catch (\Throwable) {
            }
        }

        // Drop old column
        if (Schema::hasColumn('procedure_settings', 'escalation_user_id')) {
            Schema::table('procedure_settings', function (Blueprint $table) {
                $table->dropColumn('escalation_user_id');
            });
        }

        // Add new column
        Schema::table('procedure_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('escalation_management_hierarchy_id')->nullable();
        });

        // Add new FK
        if (Schema::hasTable('management_hierarchies')) {
            Schema::table('procedure_settings', function (Blueprint $table) {
                $table->foreign('escalation_management_hierarchy_id', self::NEW_FK)
                    ->references('id')
                    ->on('management_hierarchies')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('procedure_settings')) {
            return;
        }

        // Drop new FK
        try {
            Schema::table('procedure_settings', function (Blueprint $table) {
                $table->dropForeign(self::NEW_FK);
            });
        } catch (\Throwable) {
            try {
                Schema::table('procedure_settings', function (Blueprint $table) {
                    $table->dropForeign(['escalation_management_hierarchy_id']);
                });
            } catch (\Throwable) {
            }
        }

        // Drop new column
        if (Schema::hasColumn('procedure_settings', 'escalation_management_hierarchy_id')) {
            Schema::table('procedure_settings', function (Blueprint $table) {
                $table->dropColumn('escalation_management_hierarchy_id');
            });
        }

        // Restore old column
        Schema::table('procedure_settings', function (Blueprint $table) {
            $table->uuid('escalation_user_id')->nullable();
        });

        // Restore old FK
        if (Schema::hasTable('users')) {
            Schema::table('procedure_settings', function (Blueprint $table) {
                $table->foreign('escalation_user_id', self::OLD_FK)
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }
};
