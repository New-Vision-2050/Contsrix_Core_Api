<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class ExportWarehousRequest extends FormRequest
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
            'ids.*' => ['uuid', 'exists:warehouses,id'],
            'format' => ['nullable', 'string', 'in:xlsx,csv'],
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
            'country_id' => ['nullable', 'uuid', 'exists:countries,id'],
            'city_id' => ['nullable', 'uuid', 'exists:cities,id'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'has_products' => ['nullable', 'boolean'],
            'min_products_count' => ['nullable', 'integer', 'min:0'],
            'max_products_count' => ['nullable', 'integer', 'min:0'],
            'district' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'latitude_from' => ['nullable', 'numeric', 'between:-90,90'],
            'latitude_to' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_from' => ['nullable', 'numeric', 'between:-180,180'],
            'longitude_to' => ['nullable', 'numeric', 'between:-180,180'],
            'near_location' => ['nullable', 'array'],
            'near_location.latitude' => ['required_with:near_location', 'numeric', 'between:-90,90'],
            'near_location.longitude' => ['required_with:near_location', 'numeric', 'between:-180,180'],
            'near_location.radius' => ['nullable', 'numeric', 'min:0.1', 'max:1000'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
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
            'ids.array' => 'معرفات المستودعات يجب أن تكون مصفوفة',
            'ids.*.uuid' => 'معرف المستودع يجب أن يكون UUID صحيح',
            'ids.*.exists' => 'المستودع المحدد غير موجود',
            'format.string' => 'صيغة التصدير يجب أن تكون نص',
            'format.in' => 'صيغة التصدير يجب أن تكون xlsx أو csv',
            'company_id.uuid' => 'معرف الشركة يجب أن يكون UUID صحيح',
            'company_id.exists' => 'الشركة المحددة غير موجودة',
            'country_id.uuid' => 'معرف الدولة يجب أن يكون UUID صحيح',
            'country_id.exists' => 'الدولة المحددة غير موجودة',
            'city_id.uuid' => 'معرف المدينة يجب أن يكون UUID صحيح',
            'city_id.exists' => 'المدينة المحددة غير موجودة',
            'is_active.boolean' => 'حالة النشاط يجب أن تكون صحيح أو خطأ',
            'is_default.boolean' => 'المستودع الافتراضي يجب أن يكون صحيح أو خطأ',
            'has_products.boolean' => 'وجود منتجات يجب أن يكون صحيح أو خطأ',
            'min_products_count.integer' => 'الحد الأدنى لعدد المنتجات يجب أن يكون رقم صحيح',
            'min_products_count.min' => 'الحد الأدنى لعدد المنتجات يجب أن يكون 0 أو أكثر',
            'max_products_count.integer' => 'الحد الأقصى لعدد المنتجات يجب أن يكون رقم صحيح',
            'max_products_count.min' => 'الحد الأقصى لعدد المنتجات يجب أن يكون 0 أو أكثر',
            'district.string' => 'المنطقة يجب أن تكون نص',
            'district.max' => 'المنطقة يجب ألا تتجاوز 255 حرف',
            'street.string' => 'الشارع يجب أن يكون نص',
            'street.max' => 'الشارع يجب ألا يتجاوز 255 حرف',
            'latitude_from.numeric' => 'خط العرض من يجب أن يكون رقم',
            'latitude_from.between' => 'خط العرض من يجب أن يكون بين -90 و 90',
            'latitude_to.numeric' => 'خط العرض إلى يجب أن يكون رقم',
            'latitude_to.between' => 'خط العرض إلى يجب أن يكون بين -90 و 90',
            'longitude_from.numeric' => 'خط الطول من يجب أن يكون رقم',
            'longitude_from.between' => 'خط الطول من يجب أن يكون بين -180 و 180',
            'longitude_to.numeric' => 'خط الطول إلى يجب أن يكون رقم',
            'longitude_to.between' => 'خط الطول إلى يجب أن يكون بين -180 و 180',
            'near_location.array' => 'الموقع القريب يجب أن يكون مصفوفة',
            'near_location.latitude.required_with' => 'خط العرض مطلوب مع الموقع القريب',
            'near_location.latitude.numeric' => 'خط العرض يجب أن يكون رقم',
            'near_location.latitude.between' => 'خط العرض يجب أن يكون بين -90 و 90',
            'near_location.longitude.required_with' => 'خط الطول مطلوب مع الموقع القريب',
            'near_location.longitude.numeric' => 'خط الطول يجب أن يكون رقم',
            'near_location.longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180',
            'near_location.radius.numeric' => 'نصف القطر يجب أن يكون رقم',
            'near_location.radius.min' => 'نصف القطر يجب أن يكون 0.1 كم على الأقل',
            'near_location.radius.max' => 'نصف القطر يجب ألا يتجاوز 1000 كم',
            'created_from.date' => 'تاريخ الإنشاء من يجب أن يكون تاريخ صحيح',
            'created_to.date' => 'تاريخ الإنشاء إلى يجب أن يكون تاريخ صحيح',
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
