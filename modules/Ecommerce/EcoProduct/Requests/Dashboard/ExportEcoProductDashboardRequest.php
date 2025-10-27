<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class ExportEcoProductDashboardRequest extends FormRequest
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
            'ids.*' => ['uuid', 'exists:eco_products,id'],
            'format' => ['nullable', 'string', 'in:xlsx,csv'],
            'category_id' => ['nullable', 'uuid', 'exists:eco_categories,id'],
            'sub_category_id' => ['nullable', 'uuid', 'exists:eco_categories,id'],
            'brand_id' => ['nullable', 'uuid', 'exists:eco_brands,id'],
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'price_from' => ['nullable', 'numeric', 'min:0'],
            'price_to' => ['nullable', 'numeric', 'min:0'],
            'stock_status' => ['nullable', 'string', 'in:in_stock,out_of_stock,low_stock'],
            'is_visible' => ['nullable', 'boolean'],
            'has_discount' => ['nullable', 'boolean'],
            'requires_shipping' => ['nullable', 'boolean'],
            'is_taxable' => ['nullable', 'boolean'],
            'gender' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
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
            'ids.array' => 'معرفات المنتجات يجب أن تكون مصفوفة',
            'ids.*.uuid' => 'معرف المنتج يجب أن يكون UUID صحيح',
            'ids.*.exists' => 'المنتج المحدد غير موجود',
            'format.string' => 'صيغة التصدير يجب أن تكون نص',
            'format.in' => 'صيغة التصدير يجب أن تكون xlsx أو csv',
            'category_id.uuid' => 'معرف الفئة يجب أن يكون UUID صحيح',
            'category_id.exists' => 'الفئة المحددة غير موجودة',
            'sub_category_id.uuid' => 'معرف الفئة الفرعية يجب أن يكون UUID صحيح',
            'sub_category_id.exists' => 'الفئة الفرعية المحددة غير موجودة',
            'brand_id.uuid' => 'معرف العلامة التجارية يجب أن يكون UUID صحيح',
            'brand_id.exists' => 'العلامة التجارية المحددة غير موجودة',
            'warehouse_id.uuid' => 'معرف المستودع يجب أن يكون UUID صحيح',
            'warehouse_id.exists' => 'المستودع المحدد غير موجود',
            'price_from.numeric' => 'السعر من يجب أن يكون رقم',
            'price_from.min' => 'السعر من يجب أن يكون 0 أو أكثر',
            'price_to.numeric' => 'السعر إلى يجب أن يكون رقم',
            'price_to.min' => 'السعر إلى يجب أن يكون 0 أو أكثر',
            'stock_status.string' => 'حالة المخزون يجب أن تكون نص',
            'stock_status.in' => 'حالة المخزون يجب أن تكون: متوفر، غير متوفر، أو قليل',
            'is_visible.boolean' => 'حالة الرؤية يجب أن تكون صحيح أو خطأ',
            'has_discount.boolean' => 'وجود خصم يجب أن يكون صحيح أو خطأ',
            'requires_shipping.boolean' => 'يتطلب شحن يجب أن يكون صحيح أو خطأ',
            'is_taxable.boolean' => 'خاضع للضريبة يجب أن يكون صحيح أو خطأ',
            'gender.string' => 'الجنس يجب أن يكون نص',
            'type.string' => 'النوع يجب أن يكون نص',
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
