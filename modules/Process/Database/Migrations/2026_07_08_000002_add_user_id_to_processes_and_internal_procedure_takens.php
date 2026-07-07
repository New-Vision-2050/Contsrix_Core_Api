<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add user_id to processes table.
        //    When null → shared process (default, backward compatible).
        //    When set  → independent process scoped to a specific assigned user.
        Schema::table('processes', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->after('processable_type');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['processable_id', 'processable_type', 'user_id']);
        });

        // Drop the old unique constraint and replace with one that includes user_id
        // so independent processes can share the same sort_order per user.
        Schema::table('processes', function (Blueprint $table) {
            $table->dropUnique('processes_morph_type_sort_unique');
            $table->unique(
                ['processable_id', 'processable_type', 'sort_order', 'user_id'],
                'processes_morph_type_sort_user_unique',
            );
        });

        // 2. Add user_id to internal_procedure_takens table.
        //    When null → shared procedure taken (default).
        //    When set  → procedure taken independently by a specific user.
        Schema::table('internal_procedure_takens', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->after('procedure_setting_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['processable_type', 'processable_id', 'user_id']);
        });

        // Drop the old unique constraint and replace with one that includes user_id
        // so the same procedure can be taken independently by multiple users.
        Schema::table('internal_procedure_takens', function (Blueprint $table) {
            $table->dropUnique('ipt_unique_processable_procedure');
            $table->unique(
                ['processable_type', 'processable_id', 'procedure_setting_id', 'user_id'],
                'ipt_unique_processable_procedure_user',
            );
        });
    }

    public function down(): void
    {
        Schema::table('internal_procedure_takens', function (Blueprint $table) {
            $table->dropUnique('ipt_unique_processable_procedure_user');
            $table->unique(
                ['processable_type', 'processable_id', 'procedure_setting_id'],
                'ipt_unique_processable_procedure',
            );
            $table->dropIndex(['processable_type', 'processable_id', 'user_id']);
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
