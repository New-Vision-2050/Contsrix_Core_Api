<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * max_over_time is stored in HOURS (decimal) while the rules API speaks MINUTES
 * (PATCH /attendance/constraints/{id}/rules writes round(minutes / 60, 4)).
 * DECIMAL(8,1) could not hold minute-granularity values: 20 min = 0.3333h was
 * truncated to 0.3h = 18 min, so a 20-minute overtime cap silently became 18 —
 * and the rules endpoint even read back 18. Widen to 4 decimals.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE attendances MODIFY max_over_time DECIMAL(8,4) UNSIGNED NULL COMMENT 'Hours (decimal). Snapshot of constraint.max_over_time at clock-in.'");
            DB::statement("ALTER TABLE attendance_constraints MODIFY max_over_time DECIMAL(8,4) UNSIGNED NULL COMMENT 'Hours (decimal). Cap on overtime above scheduled period length.'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE attendances MODIFY max_over_time DECIMAL(8,1) UNSIGNED NULL COMMENT 'Hours (decimal). Snapshot of constraint.max_over_time at clock-in.'");
            DB::statement("ALTER TABLE attendance_constraints MODIFY max_over_time DECIMAL(8,1) UNSIGNED NULL COMMENT 'Hours (decimal). Cap on overtime above scheduled period length.'");
        }
    }
};
