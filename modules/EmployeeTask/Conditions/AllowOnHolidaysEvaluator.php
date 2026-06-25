<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Conditions;

use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\ProcedureSetting\Conditions\ConditionContext;
use Modules\ProcedureSetting\Conditions\ConditionEvaluator;
use Modules\ProcedureSetting\Conditions\ConditionResult;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;

final class AllowOnHolidaysEvaluator implements ConditionEvaluator
{
    use ResolvesUserAttendance;

    public function __construct(
        private readonly AttendanceConstraintService $attendanceConstraintService,
    ) {}

    public function condition(): InternalProcessCondition
    {
        return InternalProcessCondition::AllowOnHolidays;
    }

    public function evaluate(array $conditionData, ConditionContext $ctx): ?ConditionResult
    {
        $user = $this->loadUserWithBranchTimezone($ctx->userId);
        if ($user === null) {
            return null;
        }

        $timezone  = $this->resolveBranchTimezone($user);
        $workRules = $this->attendanceConstraintService->getTodaysWorkRulesForUser($user, null, $timezone);
        $isHoliday = (bool) ($workRules['is_holiday'] ?? false);

        if (! $isHoliday) {
            return null;
        }

        $allowOnHolidays = (bool) ($conditionData['is_active'] ?? true);

        if ($allowOnHolidays) {
            return new ConditionResult(
                key: $this->condition()->value,
                labelAr: $this->condition()->labelAr(),
                passed: true,
                exception: 'notAllowedOnHolidays',
            );
        }

        return new ConditionResult(
            key: $this->condition()->value,
            labelAr: $this->condition()->labelAr(),
            passed: false,
            message: 'Task creation is not allowed on holidays.',
            exception: 'notAllowedOnHolidays',
        );
    }
}
