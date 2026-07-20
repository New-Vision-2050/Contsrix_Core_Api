<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_notifications', function (Blueprint $table) {
            $table->uuid('contractor_representative_id')->nullable()->after('contractor_number');

            $table->foreign('contractor_representative_id', 'pn_contractor_representative_fk')
                ->references('id')
                ->on('project_contractor_representatives')
                ->nullOnDelete();

            $table->index('contractor_representative_id', 'pn_contractor_representative_idx');
        });

        Schema::table('project_notifications', function (Blueprint $table) {
            $table->dropColumn('contractor_technical_number');
            $table->dropColumn('contractor_technical_name');
        });
    }

    public function down(): void
    {
        Schema::table('project_notifications', function (Blueprint $table) {
            $table->string('contractor_technical_number')->nullable()->after('contractor_number');
            $table->string('contractor_technical_name')->nullable()->after('contractor_technical_number');
        });

        Schema::table('project_notifications', function (Blueprint $table) {
            $table->dropForeign('pn_contractor_representative_fk');
            $table->dropIndex('pn_contractor_representative_idx');
            $table->dropColumn('contractor_representative_id');
        });
    }
};
