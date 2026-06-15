<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Enums;

enum InternalProcessForm: string
{
    case StartTask           = 'start_task';
    case AssignOtherEmployee = 'assign_other_employee';
    case ExtendTaskTime      = 'extend_task_time';
    case SendForApproval     = 'send_for_approval';
    case CancelTask          = 'cancel_task';
    case ConfirmLocation     = 'confirm_location';
    case AttachAttachments   = 'attach_attachments';

    public function labelAr(): string
    {
        return match ($this) {
            self::StartTask           => 'بدء المهمة',
            self::AssignOtherEmployee => 'تحويل لموظف اخر',
            self::ExtendTaskTime      => 'تمديد وقت المهمة',
            self::SendForApproval     => 'ارسال للاعتماد',
            self::CancelTask          => 'الغاء المهمة',
            self::ConfirmLocation     => 'تأكيد الموقع',
            self::AttachAttachments   => 'ارفاق مرفقات',
        };
    }

    /** @return list<InternalProcessCondition> */
    public function conditions(): array
    {
        return match ($this) {
            self::StartTask, self::ExtendTaskTime => [
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
            self::SendForApproval => [
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
            self::StartTask           => ['employee_task'],
            self::ExtendTaskTime      => ['employee_task'],
            self::ConfirmLocation     => ['employee_task'],
            self::AssignOtherEmployee => ['employee_task'],
            self::CancelTask          => ['employee_task', 'client_request'],
            self::SendForApproval     => ['employee_task', 'client_request'],
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
