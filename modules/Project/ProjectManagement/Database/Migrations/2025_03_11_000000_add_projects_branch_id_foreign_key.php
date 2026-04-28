<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Runs after management_hierarchies exists (2025_03_10_*).
     */
    public function up(): void
    {
        if (! Schema::hasTable('management_hierarchies') || ! Schema::hasTable('projects')) {
            return;
        }

        if (! Schema::hasColumn('projects', 'branch_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('branch_id')
                ->references('id')
                ->on('management_hierarchies')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });
    }
};
