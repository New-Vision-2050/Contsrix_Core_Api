<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterProjectOrderPermitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'order_permit_id' => ['nullable', 'integer', 'exists:order_permit,id'],
            'assigned_date_from' => ['nullable', 'date'],
            'assigned_date_to' => ['nullable', 'date'],
            'state_id' => ['nullable', 'string', 'exists:states,id'],
            'contractor_id' => ['nullable', 'string', 'exists:project_contractors,id'],
            'department_id' => ['nullable', 'integer', 'exists:order_permit_department,id'],
        ];
    }
}
