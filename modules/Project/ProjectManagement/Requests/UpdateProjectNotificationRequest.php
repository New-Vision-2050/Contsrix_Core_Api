<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationDTO;
use Modules\Project\ProjectManagement\Models\ProjectNotification;

class UpdateProjectNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isDraft = $this->boolean('is_draft');
        $assignedUserIdsRule = $isDraft ? ['nullable', 'array'] : ['nullable', 'array', 'min:1'];
        $assignedUserItemRule = $isDraft ? ['uuid'] : ['uuid', 'exists:users,id'];

        $existingId = $this->route('id');
        $existingNumber = $existingId
            ? ProjectNotification::query()->find($existingId)?->notification_number
            : null;

        return [
            'notification_number'         => [
                'nullable',
                'string',
                'max:50',
                Rule::when(
                    fn () => $this->input('notification_number') !== $existingNumber,
                    [Rule::unique('project_notifications', 'notification_number')->where('company_id', tenant('id'))->ignore($existingId)]
                ),
            ],
            'notification_type'           => ['nullable', 'string', 'max:255'],
            'severity'                    => ['nullable', 'string', 'in:منخفض,متوسط,عالي'],
            'work_type'                   => ['nullable', 'string', 'max:255'],
            'feeder_number'               => ['nullable', 'string', 'max:255'],
            'machine_number'              => ['nullable', 'string', 'max:255'],
            'work_description'            => ['nullable', 'string'],
            'contractor_id'               => ['nullable', 'uuid', 'exists:contractors,id'],
            'contractor_name'             => ['nullable', 'string', 'max:255'],
            'contractor_number'           => ['nullable', 'string', 'max:255'],
            'contractor_technical_number' => ['nullable', 'string', 'max:255'],
            'contractor_technical_name'   => ['nullable', 'string', 'max:255'],
            'contractor_category'         => ['nullable', 'string', 'max:255'],
            'contractor_notes'            => ['nullable', 'string'],
            'contractor_mobile'           => ['nullable', 'string', 'max:30'],
            'task_latitude'               => ['nullable', 'numeric', 'between:-90,90'],
            'task_longitude'              => ['nullable', 'numeric', 'between:-180,180'],
            'district'                    => ['nullable', 'string', 'max:255'],
            'full_address'                => ['nullable', 'string'],
            'location_radius'             => ['nullable', 'integer', 'min:1'],
            'location_link'               => ['nullable', 'string', 'max:500'],
            'repair_point'                => ['nullable', 'string', 'max:255'],
            'permit_source'               => ['nullable', 'string', 'max:255'],
            'permit_recipient'            => ['nullable', 'string', 'max:255'],
            'assigned_user_ids'           => $assignedUserIdsRule,
            'assigned_user_ids.*'          => $assignedUserItemRule,
            'all_users_can_approve'        => ['nullable', 'boolean'],
            'independent_progress'         => ['nullable', 'boolean'],
            'selected_distance_meters'    => ['nullable', 'integer'],
            'task_date'                   => ['nullable', 'date_format:Y-m-d'],
            'duration_hours'              => ['nullable', 'numeric', 'min:0.25', 'max:24'],
            'is_draft'                    => ['nullable', 'boolean'],
            'notes'                       => ['nullable', 'string'],
            'files'                       => ['nullable', 'array'],
            'files.*'                     => ['file', 'max:20480'],
            'deleted_media_ids'           => ['nullable', 'array'],
            'deleted_media_ids.*'         => ['integer', 'exists:media,id'],
            'approval_responsible_id'     => ['nullable', 'uuid'],
            'assignment_responsible_id'   => ['nullable', 'uuid'],
        ];
    }

    public function toDTO(): UpdateProjectNotificationDTO
    {
        return new UpdateProjectNotificationDTO(
            notificationNumber: $this->input('notification_number'),
            notificationType: $this->input('notification_type'),
            severity: $this->input('severity'),
            workType: $this->input('work_type'),
            feederNumber: $this->input('feeder_number'),
            machineNumber: $this->input('machine_number'),
            workDescription: $this->input('work_description'),
            contractorId: $this->input('contractor_id'),
            contractorName: $this->input('contractor_name'),
            contractorNumber: $this->input('contractor_number'),
            contractorTechnicalNumber: $this->input('contractor_technical_number'),
            contractorTechnicalName: $this->input('contractor_technical_name'),
            contractorCategory: $this->input('contractor_category'),
            contractorNotes: $this->input('contractor_notes'),
            contractorMobile: $this->input('contractor_mobile'),
            taskLatitude: $this->filled('task_latitude') ? (float) $this->input('task_latitude') : null,
            taskLongitude: $this->filled('task_longitude') ? (float) $this->input('task_longitude') : null,
            district: $this->input('district'),
            fullAddress: $this->input('full_address'),
            locationRadius: $this->filled('location_radius') ? (int) $this->input('location_radius') : null,
            locationLink: $this->input('location_link'),
            repairPoint: $this->input('repair_point'),
            permitSource: $this->input('permit_source'),
            permitRecipient: $this->input('permit_recipient'),
            assignedUserIds: $this->input('assigned_user_ids'),
            allUsersCanApprove: $this->filled('all_users_can_approve') ? (bool) $this->input('all_users_can_approve') : false,
            independentProgress: $this->filled('independent_progress') ? (bool) $this->input('independent_progress') : null,
            selectedDistanceMeters: $this->filled('selected_distance_meters') ? (int) $this->input('selected_distance_meters') : null,
            taskDate: $this->input('task_date'),
            durationHours: $this->filled('duration_hours') ? (float) $this->input('duration_hours') : null,
            notes: $this->input('notes'),
            files: $this->hasFile('files') ? $this->file('files') : null,
            deletedMediaIds: $this->input('deleted_media_ids'),
            approvalResponsibleId: $this->input('approval_responsible_id'),
            assignmentResponsibleId: $this->input('assignment_responsible_id'),
            isDraft: $this->boolean('is_draft'),
        );
    }
}
