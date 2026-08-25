<?php

declare(strict_types=1);

namespace Modules\Reports\Tests\Unit\Services;

use Modules\EmployeeTask\Services\EmployeeTaskPresenceService;
use Modules\Reports\Services\ReportDataExtractionService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * A single business_date can hold several attendance rows: one per scheduled period, plus
 * rows the absence sweep flipped before the employee arrived. The attendance & absence
 * report used to freeze the day status on whichever row sorted first, which rendered a
 * worked day as غياب while still printing its clock in/out times.
 */
class ReportAttendanceDayStatusTest extends TestCase
{
    private ReportDataExtractionService $service;
    private ReflectionMethod $mergeDisplayStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ReportDataExtractionService(
            $this->createMock(EmployeeTaskPresenceService::class)
        );

        $this->mergeDisplayStatus = new ReflectionMethod($this->service, 'mergeDisplayStatus');
        $this->mergeDisplayStatus->setAccessible(true);
    }

    public function test_clock_in_row_overrides_earlier_absent_row(): void
    {
        // Real case: 07:30 period row marked absent at the deadline, employee clocked in 08:44.
        $this->assertSame('present', $this->merge('absent', 'present'));
    }

    public function test_absent_row_does_not_override_clock_in_row(): void
    {
        $this->assertSame('present', $this->merge('present', 'absent'));
    }

    public function test_holiday_outranks_present_and_absent(): void
    {
        $this->assertSame('holiday', $this->merge('present', 'holiday'));
        $this->assertSame('holiday', $this->merge('holiday', 'present'));
        $this->assertSame('holiday', $this->merge('absent', 'holiday'));
    }

    public function test_day_without_any_clock_in_stays_absent(): void
    {
        $this->assertSame('absent', $this->merge('absent', 'absent'));
    }

    private function merge(string $current, string $incoming): string
    {
        return $this->mergeDisplayStatus->invoke($this->service, $current, $incoming);
    }
}
