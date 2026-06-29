<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Unit\Conditions;

use Modules\EmployeeTask\Conditions\InsideTaskLocationEvaluator;
use Modules\ProcedureSetting\Conditions\ConditionContext;
use PHPUnit\Framework\TestCase;

final class InsideTaskLocationEvaluatorTest extends TestCase
{
    private InsideTaskLocationEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator = new InsideTaskLocationEvaluator();
    }

    public function test_returns_null_when_inactive(): void
    {
        $result = $this->evaluator->evaluate(
            ['is_active' => false, 'settings' => []],
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
            ['is_active' => true, 'settings' => ['radius_meters' => 100]],
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
        $this->assertSame('locationRequired', $result->exception);
    }

    public function test_fails_when_task_location_is_missing(): void
    {
        $result = $this->evaluator->evaluate(
            ['is_active' => true, 'settings' => ['radius_meters' => 100]],
            new ConditionContext(
                userId: 'user-id',
                companyId: 'company-id',
                branchId: null,
                currentLatitude: 24.711954,
                currentLongitude: 46.682668,
            ),
        );

        $this->assertNotNull($result);
        $this->assertFalse($result->passed);
        $this->assertSame('taskLocationMissing', $result->exception);
    }

    public function test_passes_when_inside_task_location_radius(): void
    {
        $result = $this->evaluator->evaluate(
            ['is_active' => true, 'settings' => ['radius_meters' => 100]],
            new ConditionContext(
                userId: 'user-id',
                companyId: 'company-id',
                branchId: null,
                taskLatitude: 24.711954,
                taskLongitude: 46.682668,
                currentLatitude: 24.711954,
                currentLongitude: 46.682668,
            ),
        );

        $this->assertNotNull($result);
        $this->assertTrue($result->passed);
    }

    public function test_fails_when_outside_task_location_radius(): void
    {
        $result = $this->evaluator->evaluate(
            ['is_active' => true, 'settings' => ['radius_meters' => 100]],
            new ConditionContext(
                userId: 'user-id',
                companyId: 'company-id',
                branchId: null,
                taskLatitude: 24.711954,
                taskLongitude: 46.682668,
                currentLatitude: 24.720000,
                currentLongitude: 46.690000,
            ),
        );

        $this->assertNotNull($result);
        $this->assertFalse($result->passed);
        $this->assertSame('outsideTaskLocation', $result->exception);
    }
}
