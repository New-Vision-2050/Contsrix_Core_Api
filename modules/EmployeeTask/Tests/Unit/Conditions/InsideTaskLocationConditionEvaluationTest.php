<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Unit\Conditions;

use Modules\EmployeeTask\Conditions\EmployeeTaskExceptionResolver;
use Modules\EmployeeTask\Conditions\InsideTaskLocationEvaluator;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\ProcedureSetting\Conditions\ConditionContext;
use Modules\ProcedureSetting\Conditions\ConditionEvaluationService;
use Modules\ProcedureSetting\Conditions\ConditionEvaluatorRegistry;
use PHPUnit\Framework\TestCase;

final class InsideTaskLocationConditionEvaluationTest extends TestCase
{
    public function test_throws_employee_task_exception_when_outside_task_location(): void
    {
        $service  = new ConditionEvaluationService();
        $registry = new ConditionEvaluatorRegistry([new InsideTaskLocationEvaluator()]);
        $resolver = new EmployeeTaskExceptionResolver();

        $map = [
            'inside_task_location' => [
                'key'        => 'inside_task_location',
                'is_active'  => true,
                'sort_order' => 1,
                'settings'   => ['radius_meters' => 100],
            ],
        ];

        $ctx = new ConditionContext(
            userId: 'user-id',
            companyId: 'company-id',
            branchId: null,
            currentLatitude: 24.720000,
            currentLongitude: 46.690000,
            taskLatitude: 24.711954,
            taskLongitude: 46.682668,
        );

        $this->expectException(EmployeeTaskException::class);
        $this->expectExceptionMessage('You must be at the task location to perform this action.');

        $service->evaluateAndThrow($registry, $map, $ctx, $resolver);
    }

    public function test_passes_when_inside_task_location(): void
    {
        $service  = new ConditionEvaluationService();
        $registry = new ConditionEvaluatorRegistry([new InsideTaskLocationEvaluator()]);
        $resolver = new EmployeeTaskExceptionResolver();

        $map = [
            'inside_task_location' => [
                'key'        => 'inside_task_location',
                'is_active'  => true,
                'sort_order' => 1,
                'settings'   => ['radius_meters' => 100],
            ],
        ];

        $ctx = new ConditionContext(
            userId: 'user-id',
            companyId: 'company-id',
            branchId: null,
            currentLatitude: 24.711954,
            currentLongitude: 46.682668,
            taskLatitude: 24.711954,
            taskLongitude: 46.682668,
        );

        $service->evaluateAndThrow($registry, $map, $ctx, $resolver);

        $this->assertTrue(true); // no exception means the condition passed
    }
}
