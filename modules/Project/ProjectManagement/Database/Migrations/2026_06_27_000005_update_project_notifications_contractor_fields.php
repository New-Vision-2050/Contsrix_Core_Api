<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_notifications', function (Blueprint $table) {
            $table->renameColumn('magdy_number', 'feeder_number');
            $table->string('contractor_technical_name')->nullable()->after('contractor_technical_number');
            $table->uuid('contractor_id')->nullable()->after('contractor_name');

            $table->index('contractor_id', 'pn_contractor_idx');
            $table->foreign('contractor_id', 'pn_contractor_fk')
                ->references('id')
                ->on('contractors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_notifications', function (Blueprint $table) {
            $table->dropForeign('pn_contractor_fk');
            $table->dropIndex('pn_contractor_idx');
            $table->dropColumn('contractor_id');
            $table->dropColumn('contractor_technical_name');
            $table->renameColumn('feeder_number', 'magdy_number');
        });
    }
};
