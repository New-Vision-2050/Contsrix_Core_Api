<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Find and drop any existing foreign key on contractor_id
        $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'project_notifications')
            ->where('COLUMN_NAME', 'contractor_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME');

        Schema::table('project_notifications', function (Blueprint $table) use ($foreignKeys) {
            foreach ($foreignKeys as $fkName) {
                $table->dropForeign($fkName);
            }
        });

        // Null out orphaned contractor_id values that don't exist in project_contractors
        $validIds = DB::table('project_contractors')->pluck('id')->all();
        DB::table('project_notifications')
            ->whereNotNull('contractor_id')
            ->when(! empty($validIds), fn ($q) => $q->whereNotIn('contractor_id', $validIds))
            ->when(empty($validIds), fn ($q) => $q->whereNotNull('contractor_id'))
            ->update(['contractor_id' => null]);

        // Add new FK referencing `project_contractors`
        Schema::table('project_notifications', function (Blueprint $table) {
            $table->foreign('contractor_id', 'pn_contractor_fk')
                ->references('id')
                ->on('project_contractors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'project_notifications')
            ->where('COLUMN_NAME', 'contractor_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME');

        Schema::table('project_notifications', function (Blueprint $table) use ($foreignKeys) {
            foreach ($foreignKeys as $fkName) {
                $table->dropForeign($fkName);
            }

            $table->foreign('contractor_id', 'pn_contractor_fk')
                ->references('id')
                ->on('contractors')
                ->nullOnDelete();
        });
    }
};
