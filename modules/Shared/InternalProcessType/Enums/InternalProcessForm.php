<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Enums;

use Modules\ProcedureSetting\Enums\ProcedureSettingType;

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
    case ConfirmProjectNotificationPresence = 'confirmProjectNotificationPresence';
    case UpdateProjectNotificationTask      = 'updateProjectNotificationTask';
    case UpdateProjectNotificationSiteStatus = 'updateProjectNotificationSiteStatus';
    case ProjectNotificationFine            = 'projectNotificationFine';
    case ConfirmProjectNotificationLocation = 'confirmProjectNotificationLocation';
    case ProjectNotificationWorkStoppageReport = 'projectNotificationWorkStoppageReport';
    case ProjectNotificationWorkResumption     = 'projectNotificationWorkResumption';
    case ProjectNotificationTaskPostponement   = 'projectNotificationTaskPostponement';
    case EndProjectNotificationTask         = 'endProjectNotificationTask';

    /**
     * Stable key used by the mobile inbox to decide which UI flow to open.
     * - confirm_receive: first step after creating the notification.
     * - accept_reject: any subsequent workflow step that requires approval.
     */
    public function mobileInboxActionKey(): string
    {
        return match ($this) {
            self::CreateProjectNotificationTask => 'confirm_receive',
            self::UpdateProjectNotificationTask,
            self::UpdateProjectNotificationSiteStatus,
            self::ProjectNotificationFine,
            self::ConfirmProjectNotificationLocation,
            self::ProjectNotificationWorkStoppageReport,
            self::ProjectNotificationWorkResumption,
            self::ProjectNotificationTaskPostponement,
            self::EndProjectNotificationTask => 'accept_reject',
            default => 'accept_reject',
        };
    }

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
            self::ConfirmProjectNotificationPresence => 'تأكيد استلام',
            self::UpdateProjectNotificationTask      => 'تحديث بيانات الإشعار',
            self::UpdateProjectNotificationSiteStatus => 'التحديث الدوري لحالة الموقع',
            self::ProjectNotificationFine            => 'بنود الغرامة',
            self::ConfirmProjectNotificationLocation => 'تأكيد التواجد في الموقع',
            self::ProjectNotificationWorkStoppageReport => 'محضر إيقاف أعمال',
            self::ProjectNotificationWorkResumption     => 'استئناف الأعمال',
            self::ProjectNotificationTaskPostponement   => 'تأجيل المهمة',
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
                InternalProcessCondition::InsideCustomLocations,
            ],
            self::UpdateProjectNotificationTask=> [
                InternalProcessCondition::InsideCustomLocations,
            ],
            self::ConfirmProjectNotificationLocation => [
                InternalProcessCondition::InsideTaskLocation,
            ],
            self::UpdateProjectNotificationSiteStatus => [
                InternalProcessCondition::InsideTaskLocation,
            ],
            self::ProjectNotificationFine => [
                InternalProcessCondition::InsideTaskLocation,
            ],

            self::EndProjectNotificationTask => [
                InternalProcessCondition::InsideTaskLocation,

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
     * Gaps (0 → 100 → 500 → 900) leave room for middle forms added via API.
     */
    public function sortOrder(): int
    {
        return match ($this) {
            self::CreateProjectNotificationTask => 0,
            default => match (true) {
                str_starts_with($this->value, 'create') => 100,
                str_starts_with($this->value, 'end') => 900,
                default => 500,
            },
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
            self::EndTask => ['employee_task'],
            self::CreateProjectNotificationTask => ['project_notification_task'],
            self::ConfirmProjectNotificationPresence => [],
            self::UpdateProjectNotificationTask => ['project_notification_task'],
            self::UpdateProjectNotificationSiteStatus => ['project_notification_task'],
            self::ProjectNotificationFine => ['project_notification_task'],
            self::ConfirmProjectNotificationLocation => ['project_notification_task'],
            self::ProjectNotificationWorkStoppageReport => ['project_notification_task'],
            self::ProjectNotificationWorkResumption => ['project_notification_task'],
            self::ProjectNotificationTaskPostponement => ['project_notification_task'],
            self::EndProjectNotificationTask => ['project_notification_task'],
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

    /**
     * Return the procedure-setting category type for this form.
     * Project-notification forms map to the dedicated project_notification_task
     * type; all other employee-task forms map to employee_task.
     */
    public function procedureSettingType(): ProcedureSettingType
    {
        return match ($this) {
            self::CreateProjectNotificationTask,
            self::ConfirmProjectNotificationPresence,
            self::UpdateProjectNotificationTask,
            self::UpdateProjectNotificationSiteStatus,
            self::ProjectNotificationFine,
            self::ConfirmProjectNotificationLocation,
            self::ProjectNotificationWorkStoppageReport,
            self::ProjectNotificationWorkResumption,
            self::ProjectNotificationTaskPostponement,
            self::EndProjectNotificationTask => ProcedureSettingType::ProjectNotificationTask,
            default => ProcedureSettingType::EmployeeTask,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
