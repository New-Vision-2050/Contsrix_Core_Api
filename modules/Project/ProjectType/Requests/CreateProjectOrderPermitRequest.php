<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;

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
            'work_orders.*.project_management_id' => ['nullable', 'integer', 'exists:project_managements,id'],
            'work_orders.*.projects_district_id' => ['nullable', 'integer', 'exists:projects_districts,id'],
            'work_orders.*.name' => ['required', 'string', 'max:255'],
            'work_orders.*.type' => ['nullable', 'string', 'max:255'],
            'work_orders.*.assigned_date' => ['nullable', 'date'],
            'work_orders.*.order_permit_id' => ['required', 'integer', 'exists:order_permit,id'],
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

    /**
     * تخصيص رسائل الخطأ.
     */
    public function messages(): array
    {
        return [
            'work_orders.*.name.required' => 'رقم أمر العمل مطلوب.',
            'work_orders.*.order_permit_id.required' => 'نوع أمر العمل مطلوب.',
            'work_orders.*.order_permit_id.exists' => 'نوع أمر العمل غير موجود.',
        ];
    }

    /**
     * إضافة تحقق مخصص بعد التحقق الأساسي للتأكد من عدم تكرار name + order_permit_id
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $workOrders = $this->input('work_orders', []);

            foreach ($workOrders as $index => $order) {
                $name = $order['name'] ?? null;
                $orderPermitId = $order['order_permit_id'] ?? null;

                if ($name && $orderPermitId) {
                    $exists = ProjectOrderPermit::where('name', $name)
                        ->where('order_permit_id', $orderPermitId)
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add(
                            "work_orders.{$index}.name",
                            "يوجد أمر عمل بنفس الرقم '{$name}' ونوع الأمر '{$orderPermitId}' بالفعل."
                        );
                    }
                }
            }
        });
    }
}
