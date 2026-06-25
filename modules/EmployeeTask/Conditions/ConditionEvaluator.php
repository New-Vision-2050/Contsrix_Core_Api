<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Conditions;

use Modules\ProcedureSetting\Conditions\ConditionEvaluator as BaseInterface;
use Modules\ProcedureSetting\Conditions\ConditionContext;
use Modules\ProcedureSetting\Conditions\ConditionResult;

/**
 * @deprecated Moved to Modules\ProcedureSetting\Conditions\ConditionEvaluator.
 * This stub extends the shared interface for backward compatibility.
 */
interface ConditionEvaluator extends BaseInterface
{
    public function evaluate(array $conditionData, ConditionContext $ctx): ?ConditionResult;
}
