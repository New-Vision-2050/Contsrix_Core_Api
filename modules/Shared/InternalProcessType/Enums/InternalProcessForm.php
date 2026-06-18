<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Enums;

enum InternalProcessForm: string
{
    case CreateClientRequest = 'createClientRequest';
    case CreatePriceOffer    = 'createPriceOffer';
    case CreateContract      = 'createContract';
    case CreateMeeting       = 'createMeeting';
    case CreateTask        = 'createTask';
    case StartTask         = 'startTask';
    case EndTask           = 'endTask';
    case AttachAttachments = 'attachAttachments';

    public function labelAr(): string
    {
        return match ($this) {
            self::CreateClientRequest => 'إنشاء طلب عميل',
            self::CreatePriceOffer    => 'إنشاء عرض سعر',
            self::CreateContract      => 'إنشاء عقد',
            self::CreateMeeting       => 'إنشاء اجتماع',
            self::CreateTask => 'انشاء مهمة',
            self::StartTask  => 'بدء المهمة',
            self::EndTask    => 'انهاء المهمة',
            self::AttachAttachments   => 'ارفاق مرفقات',
        };
    }

    /** @return list<InternalProcessCondition> */
    public function conditions(): array
    {
        return match ($this) {
            self::CreateTask, self::StartTask => [
                InternalProcessCondition::AllowDuringShift,
                InternalProcessCondition::AllowOutsideShift,
                InternalProcessCondition::AllowOnHolidays,
            ],
            self::EndTask => [
                InternalProcessCondition::CanExitOutsideLocation,
            ],
            self::AttachAttachments => [
                InternalProcessCondition::MaxAttachments,
            ],
            default => [],
        };
    }

    /** @return array{key: string, label_ar: string, conditions: list<array{key: string, type: string, label_ar: string}>} */
    public function toDefinition(): array
    {
        return [
            'key'        => $this->value,
            'label_ar'   => $this->labelAr(),
            'conditions' => array_map(
                static fn (InternalProcessCondition $condition): array => $condition->toDefinition(),
                $this->conditions(),
            ),
        ];
    }

    /**
     * Returns the procedure-setting category types this form is applicable to.
     * Matched against ProcedureSetting::type (ProcedureSettingType values).
     *
     * @return list<string>
     */
    public function applicableTypes(): array
    {
        return match ($this) {
            self::CreateClientRequest => ['client_request'],
            self::CreatePriceOffer    => ['price_offer'],
            self::CreateContract      => ['contract'],
            self::CreateMeeting       => ['meeting'],
            self::CreateTask        => ['employee_task'],
            self::StartTask         => ['employee_task'],
            self::EndTask           => ['employee_task'],
            self::AttachAttachments => ['client_request', 'price_offer', 'contract'],
        };
    }

    /**
     * @param string $procedureType  A ProcedureSettingType::value
     * @return list<self>
     */
    public static function forType(string $procedureType): array
    {
        return array_values(
            array_filter(
                self::cases(),
                static fn (self $form): bool => in_array($procedureType, $form->applicableTypes(), true),
            )
        );
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
