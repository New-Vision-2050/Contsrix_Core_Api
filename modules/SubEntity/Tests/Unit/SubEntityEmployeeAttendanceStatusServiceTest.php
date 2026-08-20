<?php

declare(strict_types=1);

namespace Modules\SubEntity\Tests\Unit;

use Illuminate\Support\Carbon;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Tests\Feature\Reports\BaseAttendanceReportTestCase;
use Modules\SubEntity\Services\SubEntityEmployeeAttendanceStatusService;

class SubEntityEmployeeAttendanceStatusServiceTest extends BaseAttendanceReportTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_holiday_range_expires_to_required_attendance_after_date_to(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-30 10:00:00', config('app.timezone')));

        $service = app(SubEntityEmployeeAttendanceStatusService::class);

        $payload = $service->setRequiredHolidayStatus($this->employee, 'holiday', [
            'date_from' => '2026-07-30',
            'date_to' => '2026-08-02',
        ]);

        $this->assertSame('holiday', $payload['attendance_status_code']);
        $this->assertSame('2026-07-30', $payload['attendance_date_from']);
        $this->assertSame('2026-08-02', $payload['attendance_date_to']);

        $employee = $this->employee->fresh();
        $this->assertSame('holiday', $employee->manual_attendance_status);
        $this->assertSame('2026-07-30', $employee->manual_attendance_status_since?->toDateString());
        $this->assertSame('2026-08-02', $employee->manual_attendance_status_until?->toDateString());

        foreach (['2026-07-30', '2026-08-02'] as $date) {
            $this->assertDatabaseHas('attendances', [
                'user_id' => $this->employee->id,
                'business_date' => $date,
                'status' => Attendance::STATUS_HOLIDAY,
                'is_holiday' => 1,
            ]);
        }

        $during = $service->buildRequiredHolidayStatusesForUsersByKey(
            collect(['emp' => $employee]),
            '2026-08-01'
        )->get('emp');

        $this->assertSame('holiday', $during['attendance_status_code']);
        $this->assertSame('2026-07-30', $during['attendance_date_from']);
        $this->assertSame('2026-08-02', $during['attendance_date_to']);

        $after = $service->buildRequiredHolidayStatusesForUsersByKey(
            collect(['emp' => $employee->fresh()]),
            '2026-08-03'
        )->get('emp');

        $this->assertSame('required_attendance', $after['attendance_status_code']);
        $this->assertSame('مطلوب للحضور', $after['attendance_status_label']);
        $this->assertNull($after['attendance_date_from']);
        $this->assertNull($after['attendance_date_to']);
    }
}
