<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationSiteStatusTypeDTO;

class CreateProjectNotificationSiteStatusTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_type_id' => ['required', 'integer', 'exists:project_types,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function toDTO(): CreateProjectNotificationSiteStatusTypeDTO
    {
        return new CreateProjectNotificationSiteStatusTypeDTO(
            projectTypeId: (int) $this->input('project_type_id'),
            nameAr: $this->input('name_ar'),
            nameEn: $this->input('name_en'),
            sortOrder: (int) $this->input('sort_order', 0),
            isActive: (bool) $this->input('is_active', true),
        );
    }
}
