<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationDTO;

class CreateProjectNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private function requiredUnlessDraft(): RequiredIf
    {
        return Rule::requiredIf(fn () => ! $this->boolean('is_draft'));
    }

    public function rules(): array
    {
        $isDraft = $this->boolean('is_draft');
        $userExistsRule = $isDraft ? ['uuid'] : ['uuid', 'exists:users,id'];
        $assignedUserIdsRule = $isDraft ? ['nullable', 'array'] : ['required', 'array', 'min:1'];

        $notificationNumberRules = ['nullable', 'string', 'max:50'];
        if (! $isDraft) {
            $notificationNumberRules[] = Rule::unique('project_notifications', 'notification_number')->where('company_id', tenant('id'));
        } else {
            $notificationNumberRules[] = Rule::unique('project_notifications', 'notification_number')->where('company_id', tenant('id'))->whereNot('status', 'draft');
        }

        return [
            'notification_number'         => $notificationNumberRules,
            'project_id'                  => [$this->requiredUnlessDraft(), 'uuid', 'exists:projects,id'],
            'assigned_user_ids'           => $assignedUserIdsRule,
            'assigned_user_ids.*'         => $userExistsRule,
            'all_users_can_approve'       => ['nullable', 'boolean'],
            'independent_progress'        => ['nullable', 'boolean'],
            'task_date'                   => [$this->requiredUnlessDraft(), 'date_format:Y-m-d'],
            'task_time'                   => ['nullable', 'date_format:H:i'],
            'duration_hours'              => [$this->requiredUnlessDraft(), 'numeric', 'min:0.25', 'max:24'],
            'task_latitude'               => [$this->requiredUnlessDraft(), 'numeric', 'between:-90,90'],
            'task_longitude'              => [$this->requiredUnlessDraft(), 'numeric', 'between:-180,180'],
            'is_draft'                    => ['nullable', 'boolean'],
            'notification_type'           => ['nullable', 'string', 'max:255'],
            'severity'                    => ['nullable', 'string'],
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
            'location_radius'             => ['nullable', 'integer', 'min:1'],
            'location_link'               => ['nullable', 'string', 'max:500'],
            'repair_point'                => ['nullable', 'string', 'max:255'],
            'permit_source'               => ['nullable', 'string', 'max:255'],
            'permit_recipient'            => ['nullable', 'string', 'max:255'],
            'selected_distance_meters'    => ['nullable', 'integer'],
            'notes'                       => ['nullable', 'string'],
            'files'                       => ['nullable', 'array'],
            'files.*'                     => ['file', 'max:20480'],
            'approval_responsible_id'     => ['nullable', 'uuid'],
            'assignment_responsible_id'   => ['nullable', 'uuid'],
        ];
    }

    public function toDTO(): CreateProjectNotificationDTO
    {
        $isDraft = $this->boolean('is_draft');

        return new CreateProjectNotificationDTO(
            notificationNumber: $this->input('notification_number'),
            projectId: $this->input('project_id'),
            createdByUserId: (string) $this->user()->id,
            assignedUserIds: $this->input('assigned_user_ids'),
            taskDate: $this->input('task_date'),
            taskTime: $this->input('task_time'),
            durationHours: $this->filled('duration_hours') ? (float) $this->input('duration_hours') : null,
            taskLatitude: $this->filled('task_latitude') ? (float) $this->input('task_latitude') : null,
            taskLongitude: $this->filled('task_longitude') ? (float) $this->input('task_longitude') : null,
            notificationType: $this->input('notification_type'),
            severity: $this->input('severity', 'منخفض'),
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
            locationRadius: $this->filled('location_radius') ? (int) $this->input('location_radius') : null,
            locationLink: $this->input('location_link'),
            repairPoint: $this->input('repair_point'),
            permitSource: $this->input('permit_source'),
            permitRecipient: $this->input('permit_recipient'),
            selectedDistanceMeters: $this->filled('selected_distance_meters') ? (int) $this->input('selected_distance_meters') : null,
            notes: $this->input('notes'),
            files: $this->hasFile('files') ? $this->file('files') : null,
            approvalResponsibleId: $this->input('approval_responsible_id'),
            assignmentResponsibleId: $this->input('assignment_responsible_id'),
            allUsersCanApprove: (bool) $this->input('all_users_can_approve', false),
            independentProgress: (bool) $this->input('independent_progress', true),
            isDraft: $isDraft,
        );
    }
}
