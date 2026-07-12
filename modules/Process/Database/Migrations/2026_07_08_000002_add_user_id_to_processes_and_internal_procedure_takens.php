<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add user_id to processes table.
        //    When null → shared process (default, backward compatible).
        //    When set  → independent process scoped to a specific assigned user.
        if (! Schema::hasColumn('processes', 'user_id')) {
            Schema::table('processes', function (Blueprint $table) {
                $table->uuid('user_id')->nullable()->after('processable_type');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->index(['processable_id', 'processable_type', 'user_id']);
            });
        }

        // Drop the old unique constraint and replace with one that includes user_id
        // so independent processes can share the same sort_order per user.
        try {
            Schema::table('processes', function (Blueprint $table) {
                $table->dropUnique('processes_morph_type_sort_unique');
            });
        } catch (\Throwable) {
            // Constraint may already be dropped or never existed.
        }

        $indexes = collect(DB::select("SHOW INDEXES FROM processes"))->pluck('Key_name')->unique();
        if (! $indexes->contains('processes_morph_type_sort_user_unique')) {
            Schema::table('processes', function (Blueprint $table) {
                $table->unique(
                    ['processable_id', 'processable_type', 'sort_order', 'user_id'],
                    'processes_morph_type_sort_user_unique',
                );
            });
        }

        // 2. Add user_id to internal_procedure_takens table.
        //    When null → shared procedure taken (default).
        //    When set  → procedure taken independently by a specific user.
        if (! Schema::hasColumn('internal_procedure_takens', 'user_id')) {
            Schema::table('internal_procedure_takens', function (Blueprint $table) {
                $table->uuid('user_id')->nullable()->after('procedure_setting_id');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        $iptIndexes = collect(DB::select("SHOW INDEXES FROM internal_procedure_takens"))->pluck('Key_name')->unique();
        if (! $iptIndexes->contains('ipt_user_morph_index')) {
            Schema::table('internal_procedure_takens', function (Blueprint $table) {
                $table->index(
                    ['processable_type', 'processable_id', 'user_id'],
                    'ipt_user_morph_index',
                );
            });
        }

        // Drop the old unique constraint and replace with one that includes user_id
        // so the same procedure can be taken independently by multiple users.
        try {
            Schema::table('internal_procedure_takens', function (Blueprint $table) {
                $table->dropUnique('ipt_unique_processable_procedure');
            });
        } catch (\Throwable) {
            // Constraint may already be dropped or never existed.
        }

        if (! $iptIndexes->contains('ipt_unique_processable_procedure_user')) {
            Schema::table('internal_procedure_takens', function (Blueprint $table) {
                $table->unique(
                    ['processable_type', 'processable_id', 'procedure_setting_id', 'user_id'],
                    'ipt_unique_processable_procedure_user',
                );
            });
        }
    }

    public function down(): void
    {
        Schema::table('internal_procedure_takens', function (Blueprint $table) {
            $table->dropUnique('ipt_unique_processable_procedure_user');
            $table->unique(
                ['processable_type', 'processable_id', 'procedure_setting_id'],
                'ipt_unique_processable_procedure',
            );
            $table->dropIndex('ipt_user_morph_index');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('processes', function (Blueprint $table) {
            $table->dropUnique('processes_morph_type_sort_user_unique');
            $table->unique(
                ['processable_id', 'processable_type', 'sort_order'],
                'processes_morph_type_sort_unique',
            );
            $table->dropIndex(['processable_id', 'processable_type', 'user_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
