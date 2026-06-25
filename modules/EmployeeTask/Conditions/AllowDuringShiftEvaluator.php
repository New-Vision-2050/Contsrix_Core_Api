<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Conditions;

use Carbon\Carbon;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\ProcedureSetting\Conditions\ConditionContext;
use Modules\ProcedureSetting\Conditions\ConditionEvaluator;
use Modules\ProcedureSetting\Conditions\ConditionResult;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;

final class AllowDuringShiftEvaluator implements ConditionEvaluator
{
    use ResolvesUserAttendance;

    public function __construct(
        private readonly AttendanceConstraintService $attendanceConstraintService,
    ) {}

    public function condition(): InternalProcessCondition
    {
        return InternalProcessCondition::AllowDuringShift;
    }

    public function evaluate(array $conditionData, ConditionContext $ctx): ?ConditionResult
    {
        if (! ($conditionData['is_active'] ?? false)) {
            return null;
        }

        $user = $this->loadUserWithBranchTimezone($ctx->userId);
        if ($user === null) {
            return null;
        }

        $timezone = $this->resolveBranchTimezone($user);
        $settings = $conditionData['settings'] ?? [];
        $mode     = $settings['mode'] ?? 'shift';

        // ── specific_time mode ──────────────────────────────────────────
        if ($mode === 'specific_time') {
            $startTime = $settings['start_time'] ?? '00:00';
            $endTime   = $settings['end_time']   ?? '23:59';
            $now       = Carbon::now($timezone);
            $start     = Carbon::parse($startTime, $timezone)->setDateFrom($now);
            $end       = Carbon::parse($endTime, $timezone)->setDateFrom($now);

            if (! ($now->lt($start) || $now->gt($end))) {
                return new ConditionResult(
                    key: $this->condition()->value,
                    labelAr: $this->condition()->labelAr(),
                    passed: true,
                );
            }

            return new ConditionResult(
                key: $this->condition()->value,
                labelAr: $this->condition()->labelAr(),
                passed: false,
                message: 'Current time is outside the allowed shift window.',
                exception: 'outsideShiftTimeWindow',
            );
        }

        // ── shift mode (default) ────────────────────────────────────────
        $workRules = $this->attendanceConstraintService->getTodaysWorkRulesForUser($user, null, $timezone);
        $isHoliday = (bool) ($workRules['is_holiday'] ?? false);

        if ($isHoliday) {
            return null; // holiday logic handled by AllowOnHolidaysEvaluator
        }

        $isDuringShift = $this->isCurrentlyInAnyWorkPeriod($workRules['all_work_periods'] ?? []);

        if ($isDuringShift) {
            return new ConditionResult(
                key: $this->condition()->value,
                labelAr: $this->condition()->labelAr(),
                passed: true,
                exception: 'notAllowedDuringShift',
            );
        }

        return new ConditionResult(
            key: $this->condition()->value,
            labelAr: $this->condition()->labelAr(),
            passed: false,
            message: 'You are not currently within your work shift.',
            exception: 'notAllowedDuringShift',
        );
    }
}
