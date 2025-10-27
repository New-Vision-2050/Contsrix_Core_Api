<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class ExportEcoCategoryDashboardRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['nullable', 'array'],
            'ids.*' => ['uuid', 'exists:eco_categories,id'],
            'format' => ['nullable', 'string', 'in:xlsx,csv'],
            'include_inactive' => ['nullable', 'boolean'],
            'parent_id' => ['nullable', 'uuid', 'exists:eco_categories,id'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.array' => 'معرفات الفئات يجب أن تكون مصفوفة',
            'ids.*.uuid' => 'معرف الفئة يجب أن يكون UUID صحيح',
            'ids.*.exists' => 'الفئة المحددة غير موجودة',
            'format.string' => 'صيغة التصدير يجب أن تكون نص',
            'format.in' => 'صيغة التصدير يجب أن تكون xlsx أو csv',
            'include_inactive.boolean' => 'تضمين الفئات غير النشطة يجب أن يكون صحيح أو خطأ',
            'parent_id.uuid' => 'معرف الفئة الأب يجب أن يكون UUID صحيح',
            'parent_id.exists' => 'الفئة الأب المحددة غير موجودة',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add proper authorization logic here if needed
    }
}
