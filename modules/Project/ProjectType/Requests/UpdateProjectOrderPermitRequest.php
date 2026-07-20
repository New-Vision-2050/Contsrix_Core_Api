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
            'executing_entity' => ['sometimes', 'nullable', 'string', 'max:255'],
            'office' => ['sometimes', 'nullable', 'string', 'max:255'],
            'consultant_current_basket' => ['sometimes', 'nullable', 'string', 'max:255'],
            'consultant_assignment_date' => ['sometimes', 'nullable', 'date'],
            'consultant_last_procedure_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'consultant_last_procedure_date' => ['sometimes', 'nullable', 'date'],
            'consultant_column_155_entry_date' => ['sometimes', 'nullable', 'date'],
            'contractor_last_procedure_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contractor_last_procedure_date' => ['sometimes', 'nullable', 'date'],
            'contractor_column_155_entry_date' => ['sometimes', 'nullable', 'date'],
            'material_balance_elec_contractor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contractor_work_order_status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contractor_basket' => ['sometimes', 'nullable', 'string', 'max:255'],
            'consultant_price' => ['sometimes', 'nullable', 'numeric'],
        ];
    }
}
