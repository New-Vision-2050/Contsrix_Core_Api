<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Conditions;

use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;

/**
 * Central, module-agnostic condition evaluation engine.
 *
 * Any module that stores conditions on ProcedureSetting records can use this
 * service to evaluate them. The module provides:
 *   - its own ConditionEvaluatorRegistry (populated with module-specific evaluators)
 *   - its own ExceptionResolver (maps ConditionResult → module exception)
 *   - a ConditionContext built from the request data
 *
 * This service is registered as a singleton in ProcedureSettingServiceProvider.
 * It holds no state — the registry and resolver are passed per call so each
 * module gets isolated dispatch.
 */
final class ConditionEvaluationService
{
    /**
     * Iterate all conditions in the map, dispatch each to its registered
     * evaluator, and throw the appropriate exception on the first failure.
     *
     * @param array<string, array{key: string, is_active: bool, sort_order: int, settings: array}> $map
     *
     * @throws \Throwable
     */
    public function evaluateAndThrow(
        ConditionEvaluatorRegistry $registry,
        array $map,
        ConditionContext $ctx,
        ExceptionResolver $resolver,
    ): void {
        foreach ($map as $condKey => $condData) {
            $condEnum = InternalProcessCondition::tryFrom($condKey);
            if ($condEnum === null) {
                continue;
            }

            $evaluator = $registry->get($condEnum);
            if ($evaluator === null) {
                continue;
            }

            $result = $evaluator->evaluate($condData, $ctx);
            if ($result !== null && ! $result->passed) {
                $resolver->throwFromResult($result);
            }
        }
    }

    /**
     * Evaluate all evaluators in the given form group and return
     * individual pass/fail results without throwing.
     *
     * Conditions that are not configured in the map show as passed
     * (the admin is not enforcing them).
     *
     * @param array<string, array{key: string, is_active: bool, sort_order: int, settings: array}> $map
     *
     * @return array{
     *   all_passed: bool,
     *   conditions: list<array{key: string, label_ar: string, passed: bool, message: ?string}>
     * }
     */
    public function evaluateForResults(
        ConditionEvaluatorRegistry $registry,
        array $map,
        ConditionContext $ctx,
        string $formGroup,
    ): array {
        $results   = [];
        $allPassed = true;

        foreach ($registry->forFormGroup($formGroup) as $condKey => $evaluator) {
            $condEnum = InternalProcessCondition::tryFrom($condKey);

            if ($condEnum === null) {
                continue;
            }

            $condData = $map[$condKey] ?? null;

            $result = $condData !== null
                ? $evaluator->evaluate($condData, $ctx)
                : null;

            if ($result === null) {
                $result = new ConditionResult(
                    key: $condEnum->value,
                    labelAr: $condEnum->labelAr(),
                    passed: true,
                );
            }

            $results[] = [
                'key'      => $result->key,
                'label_ar' => $result->labelAr,
                'passed'   => $result->passed,
                'message'  => $result->message,
            ];

            if (! $result->passed) {
                $allPassed = false;
            }
        }

        return [
            'all_passed' => $allPassed,
            'conditions' => $results,
        ];
    }
}
