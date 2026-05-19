<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Enums;

/**
 * Stored in `procedure_settings.type` — keep in sync with validation + migrations.
 *
 * Allowed API / DB values: client_request | price_offer | contract | employee_task_request | employee_task_procedure
 */
enum ProcedureSettingType: string
{
    case ClientRequest          = 'client_request';
    case PriceOffer             = 'price_offer';
    case Contract               = 'contract';
    case EmployeeTaskRequest    = 'employee_task_request';
    case EmployeeTaskProcedure  = 'employee_task_procedure';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }

    /** Human-readable list for validation messages and docs. */
    public static function validationHint(): string
    {
        return implode(', ', self::values());
    }
}
