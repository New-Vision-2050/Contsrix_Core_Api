<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectManagement\DTO\FilterProjectNotificationChartsDTO;

class FilterProjectNotificationChartsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id'                 => ['nullable', 'uuid', 'exists:projects,id'],
            'status'                     => ['nullable', 'string', 'regex:/^(pending|received|confirmed_location|completed|cancelled)(,(pending|received|confirmed_location|completed|cancelled))*$/'],
            'notification_type'          => ['nullable', 'string'],
            'work_type'                  => ['nullable', 'string'],
            'contractor_name'            => ['nullable', 'string'],
            'contractor_id'              => ['nullable', 'uuid', 'exists:contractors,id'],
            'contractor_category'        => ['nullable', 'string'],
            'severity'                   => ['nullable', 'string'],
            'assigned_user_id'           => ['nullable', 'uuid'],
            'task_date'                  => ['nullable', 'date_format:Y-m-d'],
            'date_from'                  => ['nullable', 'date_format:Y-m-d'],
            'date_to'                    => ['nullable', 'date_format:Y-m-d'],
            'search'                     => ['nullable', 'string', 'max:255'],
            'contractual_engagement_key' => ['nullable', 'string', 'exists:contractual_engagements,code'],
        ];
    }

    public function toDTO(): FilterProjectNotificationChartsDTO
    {
        return new FilterProjectNotificationChartsDTO(
            projectId: $this->input('project_id'),
            status: $this->input('status'),
            notificationType: $this->input('notification_type'),
            workType: $this->input('work_type'),
            contractorName: $this->input('contractor_name'),
            contractorId: $this->input('contractor_id'),
            contractorCategory: $this->input('contractor_category'),
            severity: $this->input('severity'),
            assignedUserId: $this->input('assigned_user_id'),
            taskDate: $this->input('task_date'),
            dateFrom: $this->input('date_from'),
            dateTo: $this->input('date_to'),
            search: $this->input('search'),
            contractualEngagementKey: $this->input('contractual_engagement_key'),
        );
    }
}
