<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationSiteStatusTypeDTO;

class UpdateProjectNotificationSiteStatusTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_type_id' => ['nullable', 'integer', 'exists:project_types,id'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'notification_types' => ['nullable', 'array'],
            'notification_types.*' => ['uuid', 'exists:project_notification_types,id'],
            'keys' => ['nullable', 'array'],
            'keys.*.id' => ['nullable', 'string'],
            'keys.*.name_ar' => ['required', 'string', 'max:255'],
            'keys.*.name_en' => ['nullable', 'string', 'max:255'],
            'keys.*.key' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/'],
            'keys.*.field_type' => ['required', 'string', Rule::in(['text', 'number', 'date', 'select'])],
            'keys.*.options' => ['nullable', 'array', 'required_if:field_type,select'],
            'keys.*.options.*' => ['string', 'max:255'],
            'keys.*.show_in_site_status_updates' => ['nullable', 'boolean'],
            'keys.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'keys.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toDTO(): UpdateProjectNotificationSiteStatusTypeDTO
    {
        return new UpdateProjectNotificationSiteStatusTypeDTO(
            projectTypeId: $this->filled('project_type_id') ? (int) $this->input('project_type_id') : null,
            nameAr: $this->input('name_ar'),
            nameEn: $this->input('name_en'),
            sortOrder: $this->filled('sort_order') ? (int) $this->input('sort_order') : null,
            isActive: $this->filled('is_active') ? (bool) $this->input('is_active') : null,
            keys: $this->input('keys'),
            notificationTypes: $this->input('notification_types'),
        );
    }
}
