<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProjectOrderPermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'string', 'exists:projects,id'],
            'work_orders' => ['required', 'array', 'min:1'],
            'work_orders.*.name' => ['required', 'string', 'max:255'],
            'work_orders.*.type' => ['nullable', 'string', 'max:255'],
            'work_orders.*.assigned_date' => ['nullable', 'date'],
            'work_orders.*.order_permit_id' => ['nullable', 'integer', 'exists:order_permit,id'],
            'work_orders.*.order_permit_department_id' => ['nullable', 'integer', 'exists:order_permit_department,id'],
            'work_orders.*.contractor_id' => ['nullable', 'string', 'exists:project_contractors,id'],
            'work_orders.*.state_id' => ['nullable', 'string', 'exists:states,id'],
            'work_orders.*.lat' => ['nullable', 'numeric'],
            'work_orders.*.long' => ['nullable', 'numeric'],
            'work_orders.*.price' => ['nullable', 'numeric'],
            'work_orders.*.executing_entity' => ['nullable', 'string', 'max:255'],
            'work_orders.*.office' => ['nullable', 'string', 'max:255'],
            'work_orders.*.consultant_current_basket' => ['nullable', 'string', 'max:255'],
            'work_orders.*.consultant_assignment_date' => ['nullable', 'date'],
            'work_orders.*.consultant_last_procedure_code' => ['nullable', 'string', 'max:255'],
            'work_orders.*.consultant_last_procedure_date' => ['nullable', 'date'],
            'work_orders.*.consultant_column_155_entry_date' => ['nullable', 'date'],
            'work_orders.*.contractor_last_procedure_code' => ['nullable', 'string', 'max:255'],
            'work_orders.*.contractor_last_procedure_date' => ['nullable', 'date'],
            'work_orders.*.contractor_column_155_entry_date' => ['nullable', 'date'],
            'work_orders.*.material_balance_elec_contractor' => ['nullable', 'string', 'max:255'],
            'work_orders.*.contractor_work_order_status' => ['nullable', 'string', 'max:255'],
            'work_orders.*.contractor_basket' => ['nullable', 'string', 'max:255'],
            'work_orders.*.consultant_price' => ['nullable', 'numeric'],
        ];
    }
}
