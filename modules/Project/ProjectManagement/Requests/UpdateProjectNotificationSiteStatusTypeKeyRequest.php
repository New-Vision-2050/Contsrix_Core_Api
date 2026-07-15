<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationSiteStatusTypeKeyDTO;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusTypeKey;

class UpdateProjectNotificationSiteStatusTypeKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $keyId = $this->route('key_id');

        return [
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'key' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('project_notification_site_status_type_keys', 'key')->ignore($keyId),
            ],
            'field_type' => ['nullable', 'string', Rule::in(['text', 'number', 'date', 'select'])],
            'options' => ['nullable', 'array', 'required_if:field_type,select'],
            'options.*' => ['string', 'max:255'],
            'show_in_site_status_updates' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toDTO(): UpdateProjectNotificationSiteStatusTypeKeyDTO
    {
        return new UpdateProjectNotificationSiteStatusTypeKeyDTO(
            nameAr: $this->input('name_ar'),
            nameEn: $this->input('name_en'),
            key: $this->input('key'),
            fieldType: $this->input('field_type'),
            options: $this->input('options'),
            showInSiteStatusUpdates: $this->filled('show_in_site_status_updates') ? (bool) $this->input('show_in_site_status_updates') : null,
            sortOrder: $this->filled('sort_order') ? (int) $this->input('sort_order') : null,
            isActive: $this->filled('is_active') ? (bool) $this->input('is_active') : null,
        );
    }
}
