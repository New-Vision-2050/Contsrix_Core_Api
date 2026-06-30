<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Conditions;

use Modules\EmployeeTask\Support\GeoDistance;
use Modules\ProcedureSetting\Conditions\ConditionContext;
use Modules\ProcedureSetting\Conditions\ConditionEvaluator;
use Modules\ProcedureSetting\Conditions\ConditionResult;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;

final class InsideTaskLocationEvaluator implements ConditionEvaluator
{
    public function condition(): InternalProcessCondition
    {
        return InternalProcessCondition::InsideTaskLocation;
    }

    public function evaluate(array $conditionData, ConditionContext $ctx): ?ConditionResult
    {
        if (! ($conditionData['is_active'] ?? false)) {
            return null;
        }

        $radius = (int) ($conditionData['settings']['radius_meters'] ?? 100);

        if ($ctx->currentLatitude === null || $ctx->currentLongitude === null) {
            return new ConditionResult(
                key: $this->condition()->value,
                labelAr: $this->condition()->labelAr(),
                passed: false,
                message: 'Current location is required to verify task location proximity.',
                exception: 'locationRequired',
            );
        }

        if ($ctx->taskLatitude === null || $ctx->taskLongitude === null) {
            return new ConditionResult(
                key: $this->condition()->value,
                labelAr: $this->condition()->labelAr(),
                passed: false,
                message: 'Task location is not set.',
                exception: 'taskLocationMissing',
            );
        }

        $distance = GeoDistance::metres(
            $ctx->taskLatitude,
            $ctx->taskLongitude,
            $ctx->currentLatitude,
            $ctx->currentLongitude,
        );

        if ($distance <= $radius) {
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
            message: 'You must be at the task location to perform this action.',
            exception: 'outsideTaskLocation',
            context: [
                'distance_meters' => round($distance, 2),
                'radius_meters'   => $radius,
            ],
        );
    }
}
