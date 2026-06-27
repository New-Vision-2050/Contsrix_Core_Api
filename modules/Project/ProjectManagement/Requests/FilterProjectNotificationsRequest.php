<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectManagement\DTO\FilterProjectNotificationDTO;

class FilterProjectNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'        => ['nullable', 'uuid', 'exists:projects,id'],
            'status'            => ['nullable', 'string'],
            'notification_type' => ['nullable', 'string'],
            'work_type'         => ['nullable', 'string'],
            'contractor_name'   => ['nullable', 'string'],
            'contractor_id'     => ['nullable', 'uuid', 'exists:contractors,id'],
            'assigned_user_id'  => ['nullable', 'uuid'],
            'task_date'         => ['nullable', 'date_format:Y-m-d'],
            'date_from'         => ['nullable', 'date_format:Y-m-d'],
            'date_to'           => ['nullable', 'date_format:Y-m-d'],
            'search'            => ['nullable', 'string', 'max:255'],
            'per_page'          => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort'              => ['nullable', 'string'],
        ];
    }

    public function toDTO(): FilterProjectNotificationDTO
    {
        return new FilterProjectNotificationDTO(
            projectId: $this->input('project_id'),
            status: $this->input('status'),
            notificationType: $this->input('notification_type'),
            workType: $this->input('work_type'),
            contractorName: $this->input('contractor_name'),
            contractorId: $this->input('contractor_id'),
            assignedUserId: $this->input('assigned_user_id'),
            taskDate: $this->input('task_date'),
            dateFrom: $this->input('date_from'),
            dateTo: $this->input('date_to'),
            search: $this->input('search'),
            perPage: (int) $this->input('per_page', 15),
            sort: $this->input('sort'),
        );
    }
}
