<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NEW_FK = 'process_steps_escalation_mh_fk';

    public function up(): void
    {
        if (! Schema::hasTable('process_steps')) {
            return;
        }

        // Drop the existing unnamed FK on escalation_user_id
        try {
            Schema::table('process_steps', function (Blueprint $table) {
                $table->dropForeign(['escalation_user_id']);
            });
        } catch (\Throwable) {
        }

        // Drop old column
        if (Schema::hasColumn('process_steps', 'escalation_user_id')) {
            Schema::table('process_steps', function (Blueprint $table) {
                $table->dropColumn('escalation_user_id');
            });
        }

        // Add new column
        Schema::table('process_steps', function (Blueprint $table) {
            $table->unsignedBigInteger('escalation_management_hierarchy_id')->nullable()->after('assigned_user_id');
        });

        // Add new FK
        if (Schema::hasTable('management_hierarchies')) {
            Schema::table('process_steps', function (Blueprint $table) {
                $table->foreign('escalation_management_hierarchy_id', self::NEW_FK)
                    ->references('id')
                    ->on('management_hierarchies')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('process_steps')) {
            return;
        }

        // Drop new FK
        try {
            Schema::table('process_steps', function (Blueprint $table) {
                $table->dropForeign(self::NEW_FK);
            });
        } catch (\Throwable) {
            try {
                Schema::table('process_steps', function (Blueprint $table) {
                    $table->dropForeign(['escalation_management_hierarchy_id']);
                });
            } catch (\Throwable) {
            }
        }

        // Drop new column
        if (Schema::hasColumn('process_steps', 'escalation_management_hierarchy_id')) {
            Schema::table('process_steps', function (Blueprint $table) {
                $table->dropColumn('escalation_management_hierarchy_id');
            });
        }

        // Restore old column
        Schema::table('process_steps', function (Blueprint $table) {
            $table->uuid('escalation_user_id')->nullable()->after('assigned_user_id');
        });

        // Restore old FK
        if (Schema::hasTable('users')) {
            Schema::table('process_steps', function (Blueprint $table) {
                $table->foreign('escalation_user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }
};
