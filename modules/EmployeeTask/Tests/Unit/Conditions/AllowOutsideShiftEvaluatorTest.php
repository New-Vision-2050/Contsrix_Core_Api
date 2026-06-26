<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Unit\Conditions;

use Mockery;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\EmployeeTask\Conditions\AllowOutsideShiftEvaluator;
use Modules\ProcedureSetting\Conditions\ConditionContext;
use PHPUnit\Framework\TestCase;

final class AllowOutsideShiftEvaluatorTest extends TestCase
{
    private AllowOutsideShiftEvaluator $evaluator;
    private AttendanceConstraintService $attendanceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attendanceService = Mockery::mock(AttendanceConstraintService::class);
        $this->evaluator         = new AllowOutsideShiftEvaluator($this->attendanceService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_null_when_outside_shift_is_allowed(): void
    {
        $result = $this->evaluator->evaluate(
            ['is_active' => true],
            new ConditionContext(
                userId: 'user-id',
                companyId: 'company-id',
                branchId: null,
            ),
        );

        $this->assertNull($result);
    }

    public function test_fails_when_current_location_is_missing(): void
    {
        $result = $this->evaluator->evaluate(
            ['is_active' => false],
            new ConditionContext(
                userId: 'user-id',
                companyId: 'company-id',
                branchId: null,
                taskLatitude: 24.711954,
                taskLongitude: 46.682668,
            ),
        );

        $this->assertNotNull($result);
        $this->assertFalse($result->passed);
        $this->assertSame('Location data is required to verify work area.', $result->message);
    }
}
