<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Conditions;

/**
 * Resolves a failed ConditionResult into a module-specific exception.
 *
 * Each module implements this interface to map ConditionResult::$exception
 * keys to its own exception classes. This keeps the central
 * ConditionEvaluationService decoupled from any module's exception hierarchy.
 *
 * Example:
 *   EmployeeTaskExceptionResolver maps 'notAllowedDuringShift'
 *   → EmployeeTaskException::notAllowedDuringShift()
 */
interface ExceptionResolver
{
    /**
     * Throw the appropriate exception for a failed condition result.
     *
     * @throws \Throwable (always — this method never returns)
     */
    public function throwFromResult(ConditionResult $result): never;
}
