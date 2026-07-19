<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_notifications', function (Blueprint $table) {
            // Drop the old FK that references `contractors`
            $table->dropForeign('pn_contractor_fk');

            // Add new FK referencing `project_contractors`
            $table->foreign('contractor_id', 'pn_contractor_fk')
                ->references('id')
                ->on('project_contractors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_notifications', function (Blueprint $table) {
            $table->dropForeign('pn_contractor_fk');

            $table->foreign('contractor_id', 'pn_contractor_fk')
                ->references('id')
                ->on('contractors')
                ->nullOnDelete();
        });
    }
};
