<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_notifications')) {
            return;
        }

        Schema::table('project_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('project_notifications', 'contractor_technician_id')) {
                $table->uuid('contractor_technician_id')->nullable()->after('contractor_representative_id');
                $table->foreign('contractor_technician_id', 'pn_contractor_technician_fk')
                    ->references('id')
                    ->on('project_contractor_representatives')
                    ->nullOnDelete();
                $table->index('contractor_technician_id', 'pn_contractor_technician_idx');
            }

            if (! Schema::hasColumn('project_notifications', 'contractor_technician_number')) {
                $table->string('contractor_technician_number')->nullable()->after('contractor_technician_id');
            }

            if (! Schema::hasColumn('project_notifications', 'pole_number')) {
                $table->string('pole_number')->nullable()->after('permit_source');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_notifications')) {
            return;
        }

        Schema::table('project_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('project_notifications', 'contractor_technician_id')) {
                $table->dropForeign('pn_contractor_technician_fk');
                $table->dropIndex('pn_contractor_technician_idx');
                $table->dropColumn('contractor_technician_id');
            }

            if (Schema::hasColumn('project_notifications', 'contractor_technician_number')) {
                $table->dropColumn('contractor_technician_number');
            }

            if (Schema::hasColumn('project_notifications', 'pole_number')) {
                $table->dropColumn('pole_number');
            }
        });
    }
};
