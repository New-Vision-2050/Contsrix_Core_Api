<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procedure_settings')) {
            return;
        }

        // Delete children first (rows with a parent), then parents.
        // The self-referencing FK (parent_id -> id) has cascadeOnDelete,
        // so ordering avoids any integrity issues during bulk deletion.
        DB::table('procedure_settings')->whereNotNull('parent_id')->delete();
        DB::table('procedure_settings')->whereNull('parent_id')->delete();
    }

    public function down(): void
    {
        // Data clearing is destructive; nothing to rollback.
    }
};
