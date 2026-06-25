<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Conditions;

use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;

/**
 * Contract for a single condition evaluator.
 *
 * Open/Closed Principle: new conditions are added by creating a new class
 * that implements this interface and registering it in the module's
 * service provider. No existing code needs to change.
 */
interface ConditionEvaluator
{
    /**
     * Which condition key this evaluator handles.
     */
    public function condition(): InternalProcessCondition;

    /**
     * Evaluate the condition.
     *
     * @param array{key: string, is_active: bool, sort_order: int, settings: array} $conditionData
     */
    public function evaluate(array $conditionData, ConditionContext $ctx): ?ConditionResult;
}
