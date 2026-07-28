<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BUG-1 backfill: normalise early_clock_in_rules inside every constraint's
 * time_rules.weekly_schedule so BOTH key shapes are present:
 *   - allowed_minutes_before  (written by the rules API)
 *   - early_period + early_unit (read by every runtime gate)
 *
 * Rows that only have one shape get the other derived from it.
 */
return new class extends Migration
{
    private const DAYS = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public function up(): void
    {
        DB::table('attendance_constraints')
            ->select(['id', 'constraint_config'])
            ->orderBy('id')
            ->chunk(100, function ($constraints) {
                foreach ($constraints as $constraint) {
                    $config = json_decode($constraint->constraint_config ?? '', true);
                    if (!is_array($config)) {
                        continue;
                    }

                    $changed = false;
                    foreach (self::DAYS as $day) {
                        $rules = $config['time_rules']['weekly_schedule'][$day]['early_clock_in_rules'] ?? null;
                        if (!is_array($rules) || $rules === []) {
                            continue;
                        }

                        $minutes = $rules['early_period'] ?? $rules['allowed_minutes_before'] ?? null;
                        if ($minutes === null || !is_numeric($minutes) || (float) $minutes <= 0) {
                            continue;
                        }

                        $unit = strtolower((string) ($rules['early_unit'] ?? 'minutes'));
                        $minutes = (int) match ($unit) {
                            'hour', 'hours' => (int) round((float) $minutes * 60),
                            'day', 'days'   => (int) round((float) $minutes * 1440),
                            default         => (int) round((float) $minutes),
                        };

                        $normalised = [
                            'allowed_minutes_before' => $minutes,
                            'early_period'           => $minutes,
                            'early_unit'             => 'minutes',
                            'prevent_early_clock_in' => (bool) ($rules['prevent_early_clock_in'] ?? false),
                        ];

                        if ($rules !== $normalised) {
                            $config['time_rules']['weekly_schedule'][$day]['early_clock_in_rules'] = $normalised;
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        DB::table('attendance_constraints')
                            ->where('id', $constraint->id)
                            ->update(['constraint_config' => json_encode($config)]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Data normalisation is additive (fills missing keys); nothing to roll back.
    }
};
