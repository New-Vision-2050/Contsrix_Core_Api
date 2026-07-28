<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill expected_clock_out_time = end_time for rows created before Rules V2 so the
 * auto-close cron sweep has a sane value. New rows write the computed value at clock-in.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('attendances')
            ->whereNull('expected_clock_out_time')
            ->whereNotNull('end_time')
            ->update(['expected_clock_out_time' => DB::raw('end_time')]);
    }

    public function down(): void
    {
        // Backfill only; leave values in place on rollback.
    }
};
