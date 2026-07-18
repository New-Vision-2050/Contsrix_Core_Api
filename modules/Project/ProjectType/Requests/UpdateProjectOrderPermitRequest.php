<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectOrderPermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_management_id' => ['nullable', 'integer', 'exists:project_managements,id'],
            'projects_district_id' => ['nullable', 'integer', 'exists:projects_districts,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'assigned_date' => ['nullable', 'date'],
            'order_permit_id' => ['nullable', 'integer', 'exists:order_permit,id'],
            'order_permit_department_id' => ['nullable', 'integer', 'exists:order_permit_department,id'],
            'contractor_id' => ['nullable', 'string', 'exists:project_contractors,id'],
            'state_id' => ['nullable', 'string', 'exists:states,id'],
            'lat' => ['nullable', 'numeric'],
            'long' => ['nullable', 'numeric'],
            'price' => ['nullable', 'numeric'],
        ];
    }
}
