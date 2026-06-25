<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Conditions;

use Modules\EmployeeTask\Support\GeoPolygon;
use Modules\ProcedureSetting\Conditions\ConditionContext;
use Modules\ProcedureSetting\Conditions\ConditionEvaluator;
use Modules\ProcedureSetting\Conditions\ConditionResult;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;

final class InsideCustomLocationsEvaluator implements ConditionEvaluator
{
    public function condition(): InternalProcessCondition
    {
        return InternalProcessCondition::InsideCustomLocations;
    }

    public function evaluate(array $conditionData, ConditionContext $ctx): ?ConditionResult
    {
        if (! ($conditionData['is_active'] ?? false)) {
            return null;
        }

        $polygons = $conditionData['settings']['polygons'] ?? [];
        if (empty($polygons)) {
            return null;
        }

        if ($ctx->taskLatitude === null || $ctx->taskLongitude === null) {
            return null;
        }

        if (GeoPolygon::isPointInAnyPolygon($ctx->taskLatitude, $ctx->taskLongitude, $polygons)) {
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
            message: 'The task location must be within one of the designated areas.',
            exception: 'outsideCustomLocations',
        );
    }
}
