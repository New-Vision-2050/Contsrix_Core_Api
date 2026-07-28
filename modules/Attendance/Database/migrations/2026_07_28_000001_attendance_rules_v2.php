<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance Rules V2 — per-row rule snapshots and computed boundaries.
 *
 * Every rule that affects a persisted calculation is snapshotted onto the attendances row
 * at clock-in (like max_over_time), so later constraint edits never alter past rows.
 *
 * expected_clock_out_time / absent_at are branch-TZ wall-clock strings like every other
 * attendance datetime — do NOT add datetime casts for them (INV-28).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedInteger('required_work_minutes')->nullable()
                ->after('max_over_time')
                ->comment('Snapshot: scheduledEnd − scheduledStart at clock-in');
            $table->unsignedInteger('early_clock_in_minutes')->nullable()
                ->after('required_work_minutes')
                ->comment('Snapshot: early window before shift start (ordinary work)');
            $table->unsignedInteger('extension_minutes')->nullable()
                ->after('early_clock_in_minutes')
                ->comment('Snapshot: extension_hours_shift × 60 (ordinary work after shift end)');
            $table->unsignedInteger('can_clock_in_before_minutes')->nullable()
                ->after('extension_minutes')
                ->comment('Snapshot: first-clock-in deadline from shift start; NULL = no deadline');
            $table->json('overtime_flags')->nullable()
                ->after('can_clock_in_before_minutes')
                ->comment('Snapshot: the three overtime toggles');
            $table->dateTime('expected_clock_out_time')->nullable()
                ->after('overtime_flags')
                ->comment('Branch-TZ wall clock. Auto clock-out moment (required hours complete)');
            $table->dateTime('absent_at')->nullable()
                ->after('expected_clock_out_time')
                ->comment('Branch-TZ wall clock. Deadline that produced/will produce absence');
            $table->decimal('pre_shift_hours', 8, 2)->unsigned()->default(0)
                ->after('absent_at')
                ->comment('Net hours before start_time');
            $table->decimal('in_shift_hours', 8, 2)->unsigned()->default(0)
                ->after('pre_shift_hours')
                ->comment('Net hours between start_time and end_time');
            $table->decimal('post_shift_hours', 8, 2)->unsigned()->default(0)
                ->after('in_shift_hours')
                ->comment('Net hours after end_time');
            $table->decimal('outside_window_hours', 8, 2)->unsigned()->default(0)
                ->after('post_shift_hours')
                ->comment('Net hours in the overtime-priced outer zones');

            $table->index(['company_id', 'is_absent', 'business_date'], 'att_company_absent_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('att_company_absent_date_idx');
            $table->dropColumn([
                'required_work_minutes',
                'early_clock_in_minutes',
                'extension_minutes',
                'can_clock_in_before_minutes',
                'overtime_flags',
                'expected_clock_out_time',
                'absent_at',
                'pre_shift_hours',
                'in_shift_hours',
                'post_shift_hours',
                'outside_window_hours',
            ]);
        });
    }
};
