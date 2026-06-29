<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationSiteStatusUpdateDTO;

class RequestProjectNotificationSiteStatusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'update_date' => ['nullable', 'date_format:Y-m-d'],
            'update_time' => ['nullable', 'date_format:H:i'],
            'site_status_id' => ['nullable', 'uuid', 'exists:project_notification_site_statuses,id'],
            'current_site_status_id' => ['nullable', 'uuid', 'exists:project_notification_site_statuses,id'],
            'work_stages_completed' => ['nullable', 'string', 'max:255'],
            'current_status_description' => ['nullable', 'string'],
            'completion_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'updates_obstacles' => ['nullable', 'string'],
            'additional_notes' => ['nullable', 'string'],
            'internal_procedure_setting_id' => ['nullable', 'uuid', 'exists:procedure_settings,id'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'current_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'current_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function toDTO(): RequestProjectNotificationSiteStatusUpdateDTO
    {
        return new RequestProjectNotificationSiteStatusUpdateDTO(
            updateDate: $this->input('update_date'),
            updateTime: $this->input('update_time'),
            siteStatusId: $this->input('site_status_id'),
            currentSiteStatusId: $this->input('current_site_status_id'),
            workStagesCompleted: $this->input('work_stages_completed'),
            currentStatusDescription: $this->input('current_status_description'),
            completionPercentage: $this->filled('completion_percentage') ? (float) $this->input('completion_percentage') : null,
            updatesObstacles: $this->input('updates_obstacles'),
            additionalNotes: $this->input('additional_notes'),
            internalProcedureSettingId: $this->input('internal_procedure_setting_id'),
            files: $this->hasFile('files') ? $this->file('files') : null,
            currentLatitude: $this->filled('current_latitude') ? (float) $this->input('current_latitude') : null,
            currentLongitude: $this->filled('current_longitude') ? (float) $this->input('current_longitude') : null,
        );
    }
}
