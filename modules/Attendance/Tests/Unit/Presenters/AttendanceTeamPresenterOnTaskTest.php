<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Presenters;

use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Presenters\AttendanceTeamPresenter;
use PHPUnit\Framework\TestCase;

class AttendanceTeamPresenterOnTaskTest extends TestCase
{
    public function test_absent_without_task_stays_absent(): void
    {
        $attendance = $this->attendance([
            'id' => '21f515ea-a1fd-4211-ae9f-ed0fa1a07cff',
            'user_id' => '8c7787f5-3450-4a04-acaf-585768f7c39e',
            'status' => Attendance::STATUS_ABSENT,
            'is_absent' => 1,
            'is_late' => 0,
            'is_holiday' => 0,
            'day_status' => 'work_day',
            'start_time' => '2026-08-06 07:30:00',
            'business_date' => '2026-08-06',
            'clock_in_time' => null,
        ]);

        $payload = (new AttendanceTeamPresenter($attendance, false))->present();

        $this->assertSame(Attendance::STATUS_ABSENT, $payload['status']);
        $this->assertSame(1, $payload['is_absent']);
    }

    public function test_absent_with_task_becomes_on_task(): void
    {
        $attendance = $this->attendance([
            'id' => '21f515ea-a1fd-4211-ae9f-ed0fa1a07cff',
            'user_id' => '8c7787f5-3450-4a04-acaf-585768f7c39e',
            'status' => Attendance::STATUS_ABSENT,
            'is_absent' => 1,
            'is_late' => 0,
            'is_holiday' => 0,
            'day_status' => 'work_day',
            'start_time' => '2026-08-06 07:30:00',
            'business_date' => '2026-08-06',
            'clock_in_time' => null,
        ]);

        $payload = (new AttendanceTeamPresenter($attendance, true))->present();

        $this->assertSame('on_task', $payload['status']);
        $this->assertSame(0, $payload['is_absent']);
        $this->assertSame('2026-08-06', $payload['work_date']);
        $this->assertSame('21f515ea-a1fd-4211-ae9f-ed0fa1a07cff', $payload['id']);
    }

    /** @param array<string, mixed> $attributes */
    private function attendance(array $attributes): Attendance
    {
        $attendance = new Attendance();
        $attendance->setRawAttributes($attributes, true);
        $attendance->setRelation('user', null);
        $attendance->setRelation('appliedAttendanceConstraint', null);

        return $attendance;
    }
}
