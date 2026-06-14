<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Enums;

/**
 * Stored in `procedure_settings.type` — keep in sync with validation + migrations.
 *
 * Allowed API / DB values: client_request | price_offer | contract | employee_task_request |
 * employee_task_extension | employee_task_completion_approval
 */
enum ProcedureSettingType: string
{
    case ClientRequest       = 'client_request';
    case PriceOffer          = 'price_offer';
    case Contract            = 'contract';
    case EmployeeTaskRequest  = 'employee_task_request';
    case EmployeeTaskExtension = 'employee_task_extension';
    case EmployeeTaskApproval  = 'employee_task_completion_approval';

    public function labelAr(): string
    {
        return match ($this) {
            self::ClientRequest        => 'طلب عميل',
            self::PriceOffer           => 'عرض سعر',
            self::Contract             => 'عقد',
            self::EmployeeTaskRequest  => 'طلب مهمة عمل',
            self::EmployeeTaskExtension => 'تمديد مهمة عمل',
            self::EmployeeTaskApproval  => 'اعتماد إتمام مهمة',
        };
    }

    /** @return array{key: string, label_ar: string} */
    public function toDefinition(): array
    {
        return [
            'key'      => $this->value,
            'label_ar' => $this->labelAr(),
        ];
    }

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
