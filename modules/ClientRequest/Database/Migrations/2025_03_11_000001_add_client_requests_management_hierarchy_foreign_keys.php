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
        if (! Schema::hasTable('management_hierarchies') || ! Schema::hasTable('client_requests')) {
            return;
        }

        Schema::table('client_requests', function (Blueprint $table) {
            $table->foreign('branch_id')
                ->references('id')
                ->on('management_hierarchies')
                ->onDelete('set null');

            $table->foreign('management_id')
                ->references('id')
                ->on('management_hierarchies')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_requests')) {
            return;
        }

        Schema::table('client_requests', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['management_id']);
        });
    }
};
