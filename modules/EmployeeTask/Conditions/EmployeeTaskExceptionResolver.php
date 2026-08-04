<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Conditions;

use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\ProcedureSetting\Conditions\ConditionResult;
use Modules\ProcedureSetting\Conditions\ExceptionResolver;

/**
 * Maps ConditionResult::$exception keys to EmployeeTaskException factory methods.
 *
 * This is the module-specific bridge between the central ConditionEvaluationService
 * and EmployeeTask's exception hierarchy.
 */
final class EmployeeTaskExceptionResolver implements ExceptionResolver
{
    public function throwFromResult(ConditionResult $result): never
    {
        match ($result->exception) {
            'notAllowedDuringShift'          => throw EmployeeTaskException::notAllowedDuringShift(),
            'outsideShiftTimeWindow'         => throw EmployeeTaskException::outsideShiftTimeWindow($result->message),
            'clockInDeadlinePassed'          => throw EmployeeTaskException::clockInDeadlinePassed(
                is_string($result->context['deadline'] ?? null) ? $result->context['deadline'] : null
            ),
            'notAllowedOnHolidays'           => throw EmployeeTaskException::notAllowedOnHolidays(),
            'notAllowedOutsideLocation'      => throw EmployeeTaskException::notAllowedOutsideLocation(),
            'employeeHasNoAttendance'        => throw EmployeeTaskException::employeeHasNoAttendance(),
            'taskDurationExceedsLimit'       => throw EmployeeTaskException::taskDurationExceedsLimit(
                (int) ($result->context['maxHours'] ?? 0),
            ),
            'taskDateTooFarInFuture'         => throw EmployeeTaskException::taskDateTooFarInFuture(
                (int) ($result->context['maxDays'] ?? 0),
            ),
            'taskDateExceedsContractEndDate' => throw EmployeeTaskException::taskDateExceedsContractEndDate(),
            'outsideCustomLocations'         => throw EmployeeTaskException::outsideCustomLocations(),
            default                          => throw new EmployeeTaskException($result->message ?? 'Condition failed.', 422),
        };
    }
}
