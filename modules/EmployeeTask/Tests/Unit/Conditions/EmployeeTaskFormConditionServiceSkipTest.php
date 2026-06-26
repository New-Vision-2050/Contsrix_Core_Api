<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Unit\Conditions;

use Modules\EmployeeTask\Conditions\EmployeeTaskExceptionResolver;
use Modules\EmployeeTask\Services\EmployeeTaskFormConditionService;
use Modules\ProcedureSetting\Conditions\ConditionEvaluationService;
use Modules\ProcedureSetting\Conditions\ConditionEvaluatorRegistry;
use Modules\ProcedureSetting\Services\ActionTakerResolver;
use Modules\ProcedureSetting\Services\WorkflowEngine;
use Modules\Process\Services\ProcessWorkflowService;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class EmployeeTaskFormConditionServiceSkipTest extends TestCase
{
    private function buildService(): EmployeeTaskFormConditionService
    {
        return new EmployeeTaskFormConditionService(
            new ConditionEvaluatorRegistry(),
            new ConditionEvaluationService(),
            new EmployeeTaskExceptionResolver(),
            new WorkflowEngine(
                new ActionTakerResolver(),
                new ProcessWorkflowService(new ActionTakerResolver()),
            ),
        );
    }

    public function test_skips_realtime_conditions_for_project_notification_when_gps_missing(): void
    {
        $service    = $this->buildService();
        $reflection = new ReflectionClass($service);
        $method     = $reflection->getMethod('skipRealtimeConditionsForDashboardNotification');
        $method->setAccessible(true);

        $map = [
            InternalProcessCondition::AllowDuringShift->value => [
                'key'        => InternalProcessCondition::AllowDuringShift->value,
                'is_active'  => true,
                'sort_order' => 1,
                'settings'   => [],
            ],
            InternalProcessCondition::AllowOutsideShift->value => [
                'key'        => InternalProcessCondition::AllowOutsideShift->value,
                'is_active'  => false,
                'sort_order' => 2,
                'settings'   => [],
            ],
            InternalProcessCondition::AllowOnHolidays->value => [
                'key'        => InternalProcessCondition::AllowOnHolidays->value,
                'is_active'  => false,
                'sort_order' => 3,
                'settings'   => [],
            ],
            InternalProcessCondition::InsideCustomLocations->value => [
                'key'        => InternalProcessCondition::InsideCustomLocations->value,
                'is_active'  => true,
                'sort_order' => 4,
                'settings'   => [],
            ],
        ];

        $result = $method->invoke(
            $service,
            $map,
            InternalProcessForm::CreateProjectNotificationTask->value,
            null,
            null,
        );

        // Real-time conditions removed
        $this->assertArrayNotHasKey(InternalProcessCondition::AllowDuringShift->value, $result);
        $this->assertArrayNotHasKey(InternalProcessCondition::AllowOutsideShift->value, $result);
        $this->assertArrayNotHasKey(InternalProcessCondition::AllowOnHolidays->value, $result);

        // Only InsideCustomLocations is enforced for project-notification creation
        $this->assertArrayHasKey(InternalProcessCondition::InsideCustomLocations->value, $result);
    }

    public function test_keeps_all_conditions_for_regular_task_creation(): void
    {
        $service    = $this->buildService();
        $reflection = new ReflectionClass($service);
        $method     = $reflection->getMethod('skipRealtimeConditionsForDashboardNotification');
        $method->setAccessible(true);

        $map = [
            InternalProcessCondition::AllowDuringShift->value => [
                'key'        => InternalProcessCondition::AllowDuringShift->value,
                'is_active'  => true,
                'sort_order' => 1,
                'settings'   => [],
            ],
            InternalProcessCondition::AllowOutsideShift->value => [
                'key'        => InternalProcessCondition::AllowOutsideShift->value,
                'is_active'  => false,
                'sort_order' => 2,
                'settings'   => [],
            ],
            InternalProcessCondition::AllowOnHolidays->value => [
                'key'        => InternalProcessCondition::AllowOnHolidays->value,
                'is_active'  => false,
                'sort_order' => 3,
                'settings'   => [],
            ],
        ];

        $result = $method->invoke(
            $service,
            $map,
            InternalProcessForm::CreateTask->value,
            null,
            null,
        );

        // Nothing skipped for regular employee task creation
        $this->assertArrayHasKey(InternalProcessCondition::AllowDuringShift->value, $result);
        $this->assertArrayHasKey(InternalProcessCondition::AllowOutsideShift->value, $result);
        $this->assertArrayHasKey(InternalProcessCondition::AllowOnHolidays->value, $result);
    }

    public function test_keeps_allow_outside_shift_when_gps_is_present(): void
    {
        $service    = $this->buildService();
        $reflection = new ReflectionClass($service);
        $method     = $reflection->getMethod('skipRealtimeConditionsForDashboardNotification');
        $method->setAccessible(true);

        $map = [
            InternalProcessCondition::AllowDuringShift->value => [
                'key'        => InternalProcessCondition::AllowDuringShift->value,
                'is_active'  => true,
                'sort_order' => 1,
                'settings'   => [],
            ],
            InternalProcessCondition::AllowOutsideShift->value => [
                'key'        => InternalProcessCondition::AllowOutsideShift->value,
                'is_active'  => false,
                'sort_order' => 2,
                'settings'   => [],
            ],
            InternalProcessCondition::AllowOnHolidays->value => [
                'key'        => InternalProcessCondition::AllowOnHolidays->value,
                'is_active'  => false,
                'sort_order' => 3,
                'settings'   => [],
            ],
        ];

        $result = $method->invoke(
            $service,
            $map,
            InternalProcessForm::CreateProjectNotificationTask->value,
            24.711954,
            46.682668,
        );

        // AllowOutsideShift kept when GPS is present, but AllowDuringShift
        // and AllowOnHolidays still skipped (they depend on current time/holiday)
        $this->assertArrayHasKey(InternalProcessCondition::AllowOutsideShift->value, $result);
        $this->assertArrayNotHasKey(InternalProcessCondition::AllowDuringShift->value, $result);
        $this->assertArrayNotHasKey(InternalProcessCondition::AllowOnHolidays->value, $result);
    }
}
