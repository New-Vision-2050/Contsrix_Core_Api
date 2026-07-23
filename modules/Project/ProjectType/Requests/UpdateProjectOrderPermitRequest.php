<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;

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
            'order_permit_id' => ['sometimes', 'required', 'integer', 'exists:order_permit,id'],
            'order_permit_department_id' => ['nullable', 'integer', 'exists:order_permit_department,id'],
            'contractor_id' => ['nullable', 'string', 'exists:project_contractors,id'],
            'state_id' => ['nullable', 'string', 'exists:states,id'],
            'phase_status_id' => ['nullable', 'integer'],
            'project_completion_phase_id' => ['nullable', 'integer', 'exists:project_completion_phases,id'],
            'project_phase_status_id' => ['nullable', 'integer', 'exists:project_phase_statuses,id'],
            'connection_completion_phase_id' => ['nullable', 'integer', 'exists:connection_completion_phases,id'],
            'connection_phase_status_id' => ['nullable', 'integer', 'exists:connection_phase_statuses,id'],
            'start_permit_date' => ['nullable', 'date'],
            'end_permit_date' => ['nullable', 'date'],
            'note_from_permit_to_departments' => ['nullable', 'string'],
            'is_taked_action' => ['nullable', 'boolean'],
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
            'employee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'target_drilling' => ['sometimes', 'nullable', 'numeric'],
            'achieved_drilling' => ['sometimes', 'nullable', 'numeric'],
            'target_extention' => ['sometimes', 'nullable', 'numeric'],
            'achieved_extention' => ['sometimes', 'nullable', 'numeric'],
            'description_details' => ['sometimes', 'nullable', 'string'],
            'consultant_statement' => ['sometimes', 'nullable', 'string'],
            'last_date_consultant_statement' => ['sometimes', 'nullable', 'date'],
            'consultnat_statement_status' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * تخصيص رسائل الخطأ.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'رقم أمر العمل مطلوب.',
            'order_permit_id.required' => 'نوع أمر العمل مطلوب.',
            'order_permit_id.exists' => 'نوع أمر العمل غير موجود.',
        ];
    }

    /**
     * إضافة تحقق مخصص بعد التحقق الأساسي للتأكد من عدم تكرار name + order_permit_id
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $name = $this->input('name');
            $orderPermitId = $this->input('order_permit_id');
            $currentId = $this->route('id'); // لأن مسار التحديث هو {project}/order-permits/{id}

            if ($name && $orderPermitId) {
                $exists = ProjectOrderPermit::where('name', $name)
                    ->where('order_permit_id', $orderPermitId)
                    ->where('id', '!=', $currentId)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'name',
                        "يوجد أمر عمل بنفس الرقم '{$name}' ونوع الأمر '{$orderPermitId}' بالفعل."
                    );
                }
            }
        });
    }
}
