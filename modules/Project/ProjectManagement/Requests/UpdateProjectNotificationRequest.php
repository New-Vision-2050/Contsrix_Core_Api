<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationDTO;

class UpdateProjectNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notification_type'           => ['nullable', 'string', 'max:255'],
            'severity'                    => ['nullable', 'string', 'in:منخفض,متوسط,عالي'],
            'work_type'                   => ['nullable', 'string', 'max:255'],
            'feeder_number'               => ['nullable', 'string', 'max:255'],
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
            'location_radius'             => ['nullable', 'integer', 'min:1'],
            'location_link'               => ['nullable', 'string', 'max:500'],
            'repair_point'                => ['nullable', 'string', 'max:255'],
            'assigned_user_id'            => ['nullable', 'uuid', 'exists:users,id'],
            'selected_distance_meters'    => ['nullable', 'integer'],
            'task_date'                   => ['nullable', 'date_format:Y-m-d'],
            'duration_hours'              => ['nullable', 'numeric', 'min:0.25', 'max:24'],
            'notes'                       => ['nullable', 'string'],
        ];
    }

    public function toDTO(): UpdateProjectNotificationDTO
    {
        return new UpdateProjectNotificationDTO(
            notificationType: $this->input('notification_type'),
            severity: $this->input('severity'),
            workType: $this->input('work_type'),
            feederNumber: $this->input('feeder_number'),
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
            locationRadius: $this->filled('location_radius') ? (int) $this->input('location_radius') : null,
            locationLink: $this->input('location_link'),
            repairPoint: $this->input('repair_point'),
            assignedUserId: $this->input('assigned_user_id'),
            selectedDistanceMeters: $this->filled('selected_distance_meters') ? (int) $this->input('selected_distance_meters') : null,
            taskDate: $this->input('task_date'),
            durationHours: $this->filled('duration_hours') ? (float) $this->input('duration_hours') : null,
            notes: $this->input('notes'),
        );
    }
}
