<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_TABLE = 'procedure_setting_step_concerned_users';

    private const NEW_TABLE = 'procedure_setting_step_concerned_management_hierarchies';

    private const OLD_USER_FK  = 'ps_step_cu_user_fk';

    private const NEW_MH_FK    = 'ps_step_cmh_mh_fk';

    private const OLD_UNIQUE   = 'ps_step_concerned_users_step_user_unique';

    private const NEW_UNIQUE   = 'ps_step_concerned_mh_step_mh_unique';

    public function up(): void
    {
        if (! Schema::hasTable(self::OLD_TABLE)) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            // Drop old FK by constraint name
            try {
                Schema::table(self::OLD_TABLE, function (Blueprint $table) {
                    $table->dropForeign(self::OLD_USER_FK);
                });
            } catch (\Throwable) {
                // FK may not exist or may have a different name — try by column
                try {
                    Schema::table(self::OLD_TABLE, function (Blueprint $table) {
                        $table->dropForeign(['user_id']);
                    });
                } catch (\Throwable) {
                }
            }

            // Drop old unique constraint
            try {
                Schema::table(self::OLD_TABLE, function (Blueprint $table) {
                    $table->dropUnique(self::OLD_UNIQUE);
                });
            } catch (\Throwable) {
            }

            Schema::rename(self::OLD_TABLE, self::NEW_TABLE);

            // Drop old user_id column
            if (Schema::hasColumn(self::NEW_TABLE, 'user_id')) {
                Schema::table(self::NEW_TABLE, function (Blueprint $table) {
                    $table->dropColumn('user_id');
                });
            }

            // Add new management_hierarchy_id column
            Schema::table(self::NEW_TABLE, function (Blueprint $table) {
                $table->unsignedBigInteger('management_hierarchy_id')->after('procedure_setting_step_id');
            });

            // Add FK to management_hierarchies
            if (Schema::hasTable('management_hierarchies')) {
                Schema::table(self::NEW_TABLE, function (Blueprint $table) {
                    $table->foreign('management_hierarchy_id', self::NEW_MH_FK)
                        ->references('id')
                        ->on('management_hierarchies')
                        ->cascadeOnDelete();
                });
            }

            // Recreate unique constraint
            Schema::table(self::NEW_TABLE, function (Blueprint $table) {
                $table->unique(['procedure_setting_step_id', 'management_hierarchy_id'], self::NEW_UNIQUE);
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::NEW_TABLE)) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        try {
            // Drop new unique constraint
            try {
                Schema::table(self::NEW_TABLE, function (Blueprint $table) {
                    $table->dropUnique(self::NEW_UNIQUE);
                });
            } catch (\Throwable) {
            }

            // Drop new FK
            try {
                Schema::table(self::NEW_TABLE, function (Blueprint $table) {
                    $table->dropForeign(self::NEW_MH_FK);
                });
            } catch (\Throwable) {
                try {
                    Schema::table(self::NEW_TABLE, function (Blueprint $table) {
                        $table->dropForeign(['management_hierarchy_id']);
                    });
                } catch (\Throwable) {
                }
            }

            // Drop new column
            if (Schema::hasColumn(self::NEW_TABLE, 'management_hierarchy_id')) {
                Schema::table(self::NEW_TABLE, function (Blueprint $table) {
                    $table->dropColumn('management_hierarchy_id');
                });
            }

            Schema::rename(self::NEW_TABLE, self::OLD_TABLE);

            // Restore user_id column
            Schema::table(self::OLD_TABLE, function (Blueprint $table) {
                $table->uuid('user_id')->after('procedure_setting_step_id');
            });

            // Restore FK to users
            if (Schema::hasTable('users')) {
                Schema::table(self::OLD_TABLE, function (Blueprint $table) {
                    $table->foreign('user_id', self::OLD_USER_FK)
                        ->references('id')
                        ->on('users')
                        ->cascadeOnDelete();
                });
            }

            // Restore old unique constraint
            Schema::table(self::OLD_TABLE, function (Blueprint $table) {
                $table->unique(['procedure_setting_step_id', 'user_id'], self::OLD_UNIQUE);
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};
