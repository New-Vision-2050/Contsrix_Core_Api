<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Conditions;

use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\EmployeeTask\Support\GeoDistance;
use Modules\ProcedureSetting\Conditions\ConditionContext;
use Modules\ProcedureSetting\Conditions\ConditionEvaluator;
use Modules\ProcedureSetting\Conditions\ConditionResult;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;

final class AllowOutsideShiftEvaluator implements ConditionEvaluator
{
    use ResolvesUserAttendance;

    public function __construct(
        private readonly AttendanceConstraintService $attendanceConstraintService,
    ) {}

    public function condition(): InternalProcessCondition
    {
        return InternalProcessCondition::AllowOutsideShift;
    }

    public function evaluate(array $conditionData, ConditionContext $ctx): ?ConditionResult
    {
        $allowOutsideLocation = (bool) ($conditionData['is_active'] ?? true);

        if ($allowOutsideLocation) {
            return null;
        }

        if ($ctx->currentLatitude === null || $ctx->currentLongitude === null) {
            return new ConditionResult(
                key: 'location_inside_work_area',
                labelAr: 'التواجد داخل نطاق العمل',
                passed: false,
                message: 'Location data is required to verify work area.',
            );
        }

        $user = $this->loadUserWithBranchTimezone($ctx->userId);
        if ($user === null) {
            return null;
        }

        $timezone  = $this->resolveBranchTimezone($user);
        $workRules = $this->attendanceConstraintService->getTodaysWorkRulesForUser($user, null, $timezone);

        $locations = [];
        $mainLocation = $workRules['location_work'] ?? null;
        if ($mainLocation && isset($mainLocation['latitude'], $mainLocation['longitude'], $mainLocation['radius'])) {
            $locations[] = $mainLocation;
        }
        foreach ($workRules['additional_locations'] ?? [] as $loc) {
            if (isset($loc['latitude'], $loc['longitude'], $loc['radius'])) {
                $locations[] = $loc;
            }
        }

        if (empty($locations)) {
            return null;
        }

        foreach ($locations as $loc) {
            $radius   = (int) ($loc['radius'] ?? 100);
            $distance = GeoDistance::metres(
                (float) $loc['latitude'],
                (float) $loc['longitude'],
                $ctx->currentLatitude,
                $ctx->currentLongitude,
            );

            if ($distance <= $radius) {
                return new ConditionResult(
                    key: 'location_inside_work_area',
                    labelAr: 'التواجد داخل نطاق العمل',
                    passed: true,
                );
            }
        }

        return new ConditionResult(
            key: 'location_inside_work_area',
            labelAr: 'التواجد داخل نطاق العمل',
            passed: false,
            message: 'You are outside the designated work area.',
        );
    }
}
