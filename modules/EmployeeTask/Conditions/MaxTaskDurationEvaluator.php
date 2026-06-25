<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Conditions;

use Modules\ProcedureSetting\Conditions\ConditionContext;
use Modules\ProcedureSetting\Conditions\ConditionEvaluator;
use Modules\ProcedureSetting\Conditions\ConditionResult;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;

final class MaxTaskDurationEvaluator implements ConditionEvaluator
{
    public function condition(): InternalProcessCondition
    {
        return InternalProcessCondition::MaxTaskDuration;
    }

    public function evaluate(array $conditionData, ConditionContext $ctx): ?ConditionResult
    {
        if (! ($conditionData['is_active'] ?? false)) {
            return null;
        }

        if ($ctx->durationHours === null) {
            return null;
        }

        $maxHours = (int) ($conditionData['settings']['max_hours'] ?? 8);

        if ($ctx->durationHours <= $maxHours) {
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
            message: "The task duration cannot exceed {$maxHours} hours.",
            exception: 'taskDurationExceedsLimit',
            context: ['maxHours' => $maxHours],
        );
    }
}
