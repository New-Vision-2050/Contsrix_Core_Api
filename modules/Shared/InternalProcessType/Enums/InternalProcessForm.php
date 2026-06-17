<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Enums;

enum InternalProcessForm: string
{
    case CreateClientRequest = 'createClientRequest';
    case CreatePriceOffer    = 'createPriceOffer';
    case CreateContract      = 'createContract';
    case CreateMeeting       = 'createMeeting';
    case CreateTask          = 'createTask';
    case StartTask           = 'startTask';
    case AssignOtherEmployee = 'assignOtherEmployee';
    case ExtendTaskTime      = 'extendTaskTime';
    case SendForApproval     = 'sendForApproval';
    case CancelTask          = 'cancelTask';
    case ConfirmLocation     = 'confirmLocation';
    case EndTask             = 'endTask';
    case AttachAttachments   = 'attachAttachments';

    public function labelAr(): string
    {
        return match ($this) {
            self::CreateClientRequest => 'إنشاء طلب عميل',
            self::CreatePriceOffer    => 'إنشاء عرض سعر',
            self::CreateContract      => 'إنشاء عقد',
            self::CreateMeeting       => 'إنشاء اجتماع',
            self::CreateTask          => 'انشاء مهمة',
            self::StartTask           => 'بدء المهمة',
            self::AssignOtherEmployee => 'تحويل لموظف اخر',
            self::ExtendTaskTime      => 'تمديد وقت المهمة',
            self::SendForApproval     => 'ارسال للاعتماد',
            self::CancelTask          => 'الغاء المهمة',
            self::ConfirmLocation     => 'تأكيد الموقع',
            self::EndTask             => 'انهاء المهمة',
            self::AttachAttachments   => 'ارفاق مرفقات',
        };
    }

    /** @return list<InternalProcessCondition> */
    public function conditions(): array
    {
        return match ($this) {
            self::CreateClientRequest, self::CreatePriceOffer, self::CreateContract, self::CreateMeeting, self::CreateTask, self::StartTask, self::ExtendTaskTime => [
                InternalProcessCondition::AllowDuringShift,
                InternalProcessCondition::AllowOutsideShift,
                InternalProcessCondition::AllowOnHolidays,
                InternalProcessCondition::ApplyToAllBranches,
                InternalProcessCondition::HasTaskDuration,
                InternalProcessCondition::MaxDurationHours,
            ],
            self::AssignOtherEmployee, self::CancelTask => [
                InternalProcessCondition::AllowDuringShift,
                InternalProcessCondition::AllowOutsideShift,
                InternalProcessCondition::AllowOnHolidays,
                InternalProcessCondition::ApplyToAllBranches,
            ],
            self::SendForApproval, self::EndTask => [
                InternalProcessCondition::AllowDuringShift,
                InternalProcessCondition::ApplyToAllBranches,
            ],
            self::ConfirmLocation => [
                InternalProcessCondition::ApplyToAllBranches,
            ],
            self::AttachAttachments => [
                InternalProcessCondition::ApplyToAllBranches,
                InternalProcessCondition::MaxAttachments,
            ],
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
            self::CreateTask          => ['employee_task'],
            self::StartTask           => ['employee_task'],
            self::ExtendTaskTime      => ['employee_task'],
            self::ConfirmLocation     => ['employee_task'],
            self::AssignOtherEmployee => ['employee_task'],
            self::CancelTask          => ['employee_task', 'client_request'],
            self::SendForApproval     => ['employee_task', 'client_request'],
            self::EndTask             => ['employee_task'],
            self::AttachAttachments   => ['employee_task', 'client_request', 'price_offer', 'contract'],
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
