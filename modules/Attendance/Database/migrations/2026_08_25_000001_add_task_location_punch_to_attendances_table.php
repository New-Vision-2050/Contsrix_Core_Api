<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A punch taken inside an active task's temporary geofence is ordinary attendance, but the
 * report shows its time in the task columns rather than the office ones. Geofence matching
 * discards which circle it matched, so the task has to be recorded at punch time — it cannot
 * be recovered later once the task's window closes and the geofence disappears.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'clock_in_task_id')) {
                $table->uuid('clock_in_task_id')->nullable()->after('clock_in_location');
                $table->index('clock_in_task_id');
            }

            if (! Schema::hasColumn('attendances', 'clock_out_task_id')) {
                $table->uuid('clock_out_task_id')->nullable()->after('clock_out_location');
                $table->index('clock_out_task_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            foreach (['clock_in_task_id', 'clock_out_task_id'] as $column) {
                if (Schema::hasColumn('attendances', $column)) {
                    $table->dropIndex([$column]);
                    $table->dropColumn($column);
                }
            }
        });
    }
};
