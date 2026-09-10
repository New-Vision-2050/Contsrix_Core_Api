<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Support\ManualClockOutTime;
use PHPUnit\Framework\TestCase;

class ManualClockOutTimeTest extends TestCase
{
    public function test_no_overtime_role_is_capped_at_shift_end(): void
    {
        $attendance = $this->shift('2026-09-10 08:30:00', '2026-09-10 17:30:00');

        $resolved = ManualClockOutTime::resolve($attendance, '2026-09-10 19:30:00');

        $this->assertSame('2026-09-10 17:30:00', $resolved->format('Y-m-d H:i:s'));
    }

    public function test_early_manual_clock_out_is_not_moved(): void
    {
        $attendance = $this->shift('2026-09-10 08:30:00', '2026-09-10 17:30:00');

        $resolved = ManualClockOutTime::resolve($attendance, '2026-09-10 16:00:00');

        $this->assertSame('2026-09-10 16:00:00', $resolved->format('Y-m-d H:i:s'));
    }

    public function test_expected_clock_out_is_used_when_present(): void
    {
        $attendance = $this->shift('2026-09-10 08:30:00', '2026-09-10 17:30:00');
        $attendance->expected_clock_out_time = '2026-09-10 17:00:00';

        $resolved = ManualClockOutTime::resolve($attendance, '2026-09-10 19:00:00');

        $this->assertSame('2026-09-10 17:00:00', $resolved->format('Y-m-d H:i:s'));
    }

    public function test_role_with_after_finish_overtime_keeps_the_real_punch(): void
    {
        $attendance = $this->shift('2026-09-10 08:30:00', '2026-09-10 17:30:00');
        $attendance->overtime_flags = [
            'is_overtime_before_early_clock_in' => false,
            'is_overtime_after_extension_hours_shift' => false,
            'is_after_finish_working_hours' => true,
        ];
        $attendance->max_over_time = 2.0;

        $resolved = ManualClockOutTime::resolve($attendance, '2026-09-10 19:00:00');

        $this->assertSame('2026-09-10 19:00:00', $resolved->format('Y-m-d H:i:s'));
    }

    public function test_overtime_flag_without_max_over_time_is_still_capped(): void
    {
        $attendance = $this->shift('2026-09-10 08:30:00', '2026-09-10 17:30:00');
        $attendance->overtime_flags = [
            'is_after_finish_working_hours' => true,
        ];
        $attendance->max_over_time = 0;

        $resolved = ManualClockOutTime::resolve($attendance, '2026-09-10 19:00:00');

        $this->assertSame('2026-09-10 17:30:00', $resolved->format('Y-m-d H:i:s'));
    }

    private function shift(string $start, string $end): Attendance
    {
        $attendance = new Attendance();
        $attendance->timezone = 'Asia/Riyadh';
        $attendance->clock_in_time = $start;
        $attendance->start_time = $start;
        $attendance->end_time = $end;
        $attendance->expected_clock_out_time = $end;
        $attendance->overtime_flags = [
            'is_overtime_before_early_clock_in' => false,
            'is_overtime_after_extension_hours_shift' => false,
            'is_after_finish_working_hours' => false,
        ];
        $attendance->max_over_time = 0;

        return $attendance;
    }
}
