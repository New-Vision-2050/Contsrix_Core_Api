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
    case EndTask             = 'endTask';
    case EndClientRequest    = 'endClientRequest';
    case EndPriceOffer       = 'endPriceOffer';
    case EndContract         = 'endContract';
    case EndMeeting          = 'endMeeting';
    case AttachAttachments   = 'attachAttachments';
    case CreateProjectNotificationTask = 'createProjectNotificationTask';
    case StartProjectNotificationTask    = 'startProjectNotificationTask';
    case ConfirmProjectNotificationPresence = 'confirmProjectNotificationPresence';
    case UpdateProjectNotificationTask      = 'updateProjectNotificationTask';
    case EndProjectNotificationTask         = 'endProjectNotificationTask';

    public function labelAr(): string
    {
        return match ($this) {
            self::CreateClientRequest => 'إنشاء طلب عميل',
            self::CreatePriceOffer    => 'إنشاء عرض سعر',
            self::CreateContract      => 'إنشاء عقد',
            self::CreateMeeting       => 'إنشاء اجتماع',
            self::CreateTask          => 'انشاء مهمة',
            self::StartTask           => 'بدء المهمة',
            self::EndTask             => 'انهاء المهمة',
            self::EndClientRequest    => 'انهاء طلب عميل',
            self::EndPriceOffer       => 'انهاء عرض سعر',
            self::EndContract         => 'انهاء عقد',
            self::EndMeeting          => 'انهاء اجتماع',
            self::AttachAttachments   => 'ارفاق مرفقات',
            self::CreateProjectNotificationTask => 'إنشاء إشعار مشروع',
            self::StartProjectNotificationTask    => 'تأكيد استلام',
            self::ConfirmProjectNotificationPresence => 'تأكيد التواجد',
            self::UpdateProjectNotificationTask      => 'تحديث',
            self::EndProjectNotificationTask         => 'إنهاء المهمة',
        };
    }

    /** @return list<InternalProcessCondition> */
    public function conditions(): array
    {
        return match ($this) {
            // Shift-period gating on creation; holiday gating on start.
            // Start/end timing is otherwise enforced by the Attendance module.
            self::CreateTask => [
                InternalProcessCondition::AllowDuringShift,
                InternalProcessCondition::AllowOutsideShift,
                InternalProcessCondition::AllowOnHolidays,
                InternalProcessCondition::InsideCustomLocations,
                InternalProcessCondition::MaxTaskDuration,
                InternalProcessCondition::MaxScheduledDateOffset,
            ],
            self::StartTask => [
                InternalProcessCondition::AllowOnHolidays,
            ],
            self::EndTask   => [],
            self::CreateProjectNotificationTask => [
                InternalProcessCondition::AllowDuringShift,
                InternalProcessCondition::AllowOutsideShift,
                InternalProcessCondition::AllowOnHolidays,
                InternalProcessCondition::InsideCustomLocations,
                InternalProcessCondition::MaxTaskDuration,
                InternalProcessCondition::MaxScheduledDateOffset,
            ],
            self::StartProjectNotificationTask,
            self::ConfirmProjectNotificationPresence,
            self::UpdateProjectNotificationTask,
            self::EndProjectNotificationTask => [
                InternalProcessCondition::AllowOnHolidays,
            ],
            self::AttachAttachments => [
                InternalProcessCondition::MaxAttachments,
            ],
            default => [],
        };
    }

    /**
     * Natural sort weight for this form within a procedure type.
     * create* forms are always first, end* forms are always last.
     * Gaps (100 → 500 → 900) leave room for middle forms added via API.
     */
    public function sortOrder(): int
    {
        if (str_starts_with($this->value, 'create')) {
            return 100;
        }
        if (str_starts_with($this->value, 'end')) {
            return 900;
        }
        return 500;
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
            self::CreateClientRequest,
            self::EndClientRequest    => ['client_request'],
            self::CreatePriceOffer,
            self::EndPriceOffer       => ['price_offer'],
            self::CreateContract,
            self::EndContract         => ['contract'],
            self::CreateMeeting,
            self::EndMeeting          => ['meeting'],
            self::CreateTask,
            self::StartTask,
            self::EndTask,
            self::CreateProjectNotificationTask,
            self::StartProjectNotificationTask,
            self::ConfirmProjectNotificationPresence,
            self::UpdateProjectNotificationTask,
            self::EndProjectNotificationTask => ['employee_task'],
            self::AttachAttachments   => ['client_request', 'price_offer', 'contract'],
        };
    }

    /**
     * @param string $procedureType  A ProcedureSettingType::value
     * @return list<self>             Sorted: create* first → middle → end* last
     */
    public static function forType(string $procedureType): array
    {
        $forms = array_values(
            array_filter(
                self::cases(),
                static fn (self $form): bool => in_array($procedureType, $form->applicableTypes(), true),
            )
        );

        usort($forms, static fn (self $a, self $b) => $a->sortOrder() <=> $b->sortOrder());

        return $forms;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
