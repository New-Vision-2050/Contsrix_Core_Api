<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationSiteStatusTypeKeyDTO;
use Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusType;

class CreateProjectNotificationSiteStatusTypeKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'key' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('project_notification_site_status_type_keys', 'key'),
            ],
            'field_type' => ['required', 'string', Rule::in(['text', 'number', 'date', 'select'])],
            'options' => ['nullable', 'array', 'required_if:field_type,select'],
            'options.*' => ['string', 'max:255'],
            'show_in_site_status_updates' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toDTO(string $siteStatusTypeId): CreateProjectNotificationSiteStatusTypeKeyDTO
    {
        return new CreateProjectNotificationSiteStatusTypeKeyDTO(
            siteStatusTypeId: $siteStatusTypeId,
            nameAr: $this->input('name_ar'),
            nameEn: $this->input('name_en'),
            key: $this->input('key'),
            fieldType: $this->input('field_type', 'text'),
            options: $this->input('options'),
            showInSiteStatusUpdates: (bool) $this->input('show_in_site_status_updates', false),
            sortOrder: (int) $this->input('sort_order', 0),
            isActive: (bool) $this->input('is_active', true),
        );
    }
}
