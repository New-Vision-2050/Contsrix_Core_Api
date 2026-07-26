<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Enums;

/**
 * Stored in `procedure_settings.type` — category-level identifiers only.
 * Sub-actions (extend, approve, cancel…) are InternalProcedureSettings (child rows
 * in the same table) linked via parent_id, distinguished by the `form` column.
 *
 * Allowed API / DB values: employee_task | project_notification_task | client_request | price_offer | contract | meeting
 */
enum ProcedureSettingType: string
{
    case EmployeeTask          = 'employee_task';
    case ProjectNotificationTask = 'project_notification_task';
    case ClientRequest         = 'client_request';
    case PriceOffer            = 'price_offer';
    case Contract              = 'contract';
    case Meeting               = 'meeting';

    public function labelAr(): string
    {
        return match ($this) {
            self::EmployeeTask          => 'مهمة العمل',
            self::ProjectNotificationTask => 'مهام الصيانة والطوارئ',
            self::ClientRequest         => 'طلب عميل',
            self::PriceOffer            => 'عرض سعر',
            self::Contract              => 'عقد',
            self::Meeting               => 'اجتماع',
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
