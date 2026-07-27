<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rules V2 (D4): lateness is strict — any minute past the scheduled start is recorded.
 * The grace period is dead. Zero every stored lateness grace so stale config cannot
 * resurface through legacy readers.
 */
return new class extends Migration
{
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

                    $weekly = $config['time_rules']['weekly_schedule'] ?? [];
                    foreach ($weekly as $day => $dayData) {
                        $rules = $dayData['lateness_rules'] ?? null;
                        if (!is_array($rules)) {
                            continue;
                        }
                        if ((int) ($rules['lateness_period'] ?? 0) !== 0 || (int) ($rules['grace_period_minutes'] ?? 0) !== 0) {
                            $rules['lateness_period'] = 0;
                            $rules['grace_period_minutes'] = 0;
                            $config['time_rules']['weekly_schedule'][$day]['lateness_rules'] = $rules;
                            $changed = true;
                        }
                    }

                    $topLevel = $config['time_rules']['lateness_rules'] ?? null;
                    if (is_array($topLevel)
                        && ((int) ($topLevel['lateness_period'] ?? 0) !== 0 || (int) ($topLevel['grace_period_minutes'] ?? 0) !== 0)) {
                        $topLevel['lateness_period'] = 0;
                        $topLevel['grace_period_minutes'] = 0;
                        $config['time_rules']['lateness_rules'] = $topLevel;
                        $changed = true;
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
        // Grace values are intentionally destroyed; strict lateness has no rollback of data.
    }
};
