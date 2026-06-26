<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationDTO;

class CreateProjectNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'                  => ['required', 'uuid', 'exists:projects,id'],
            'assigned_user_id'            => ['required', 'uuid', 'exists:users,id'],
            'task_date'                   => ['required', 'date_format:Y-m-d'],
            'duration_hours'              => ['required', 'numeric', 'min:0.25', 'max:24'],
            'task_latitude'               => ['required', 'numeric', 'between:-90,90'],
            'task_longitude'              => ['required', 'numeric', 'between:-180,180'],
            'notification_type'           => ['nullable', 'string', 'max:255'],
            'severity'                    => ['nullable', 'string'],
            'work_type'                   => ['nullable', 'string', 'max:255'],
            'magdy_number'                => ['nullable', 'string', 'max:255'],
            'work_description'            => ['nullable', 'string'],
            'contractor_name'             => ['nullable', 'string', 'max:255'],
            'contractor_number'           => ['nullable', 'string', 'max:255'],
            'contractor_technical_number' => ['nullable', 'string', 'max:255'],
            'contractor_category'         => ['nullable', 'string', 'max:255'],
            'contractor_notes'            => ['nullable', 'string'],
            'contractor_mobile'           => ['nullable', 'string', 'max:30'],
            'location_radius'             => ['nullable', 'integer', 'min:1'],
            'location_link'               => ['nullable', 'string', 'max:500'],
            'repair_point'                => ['nullable', 'string', 'max:255'],
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
        return new CreateProjectNotificationDTO(
            projectId: $this->input('project_id'),
            createdByUserId: (string) $this->user()->id,
            assignedUserId: $this->input('assigned_user_id'),
            taskDate: $this->input('task_date'),
            durationHours: (float) $this->input('duration_hours'),
            taskLatitude: (float) $this->input('task_latitude'),
            taskLongitude: (float) $this->input('task_longitude'),
            notificationType: $this->input('notification_type'),
            severity: $this->input('severity', 'منخفض'),
            workType: $this->input('work_type'),
            magdyNumber: $this->input('magdy_number'),
            workDescription: $this->input('work_description'),
            contractorName: $this->input('contractor_name'),
            contractorNumber: $this->input('contractor_number'),
            contractorTechnicalNumber: $this->input('contractor_technical_number'),
            contractorCategory: $this->input('contractor_category'),
            contractorNotes: $this->input('contractor_notes'),
            contractorMobile: $this->input('contractor_mobile'),
            locationRadius: $this->filled('location_radius') ? (int) $this->input('location_radius') : null,
            locationLink: $this->input('location_link'),
            repairPoint: $this->input('repair_point'),
            selectedDistanceMeters: $this->filled('selected_distance_meters') ? (int) $this->input('selected_distance_meters') : null,
            notes: $this->input('notes'),
            files: $this->hasFile('files') ? $this->file('files') : null,
            approvalResponsibleId: $this->input('approval_responsible_id'),
            assignmentResponsibleId: $this->input('assignment_responsible_id'),
        );
    }
}
