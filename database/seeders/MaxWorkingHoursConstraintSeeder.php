<?php

declare(strict_types=1);

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sets max_working_hours = 9 on all attendance_constraints that have
 * the value as NULL (i.e., rows that existed before the column was added).
 *
 * The migration column already defines DEFAULT 9, so newly inserted rows
 * are auto-populated. This seeder backfills any existing rows.
 *
 * Run: php artisan db:seed --class=MaxWorkingHoursConstraintSeeder
 */
class MaxWorkingHoursConstraintSeeder extends Seeder
{
    public function run(): void
    {
        $updated = DB::table('attendance_constraints')
            ->whereNull('max_working_hours')
            ->update(['max_working_hours' => 9]);

        $this->command->info("MaxWorkingHoursConstraintSeeder: set max_working_hours = 9 on {$updated} constraint(s).");
    }
}
