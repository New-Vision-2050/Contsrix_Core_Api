<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportDealDayRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'ids' => ['nullable', 'array'],
            'ids.*' => ['uuid', 'exists:deal_days,id'],
            'format' => ['nullable', 'string', 'in:xlsx,csv'],
            'search' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['uuid', 'exists:companies,id'],
            'product_id' => ['nullable', 'uuid', 'exists:eco_products,id'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['uuid', 'exists:eco_products,id'],
            'discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'discount_types' => ['nullable', 'array'],
            'discount_types.*' => ['string', 'in:percentage,fixed'],
            'min_discount_value' => ['nullable', 'numeric', 'min:0'],
            'max_discount_value' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'active_only' => ['nullable', 'boolean'],
            'inactive_only' => ['nullable', 'boolean'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date|after_or_equal:created_from'],
            'updated_from' => ['nullable', 'date'],
            'updated_to' => ['nullable', 'date|after_or_equal:updated_from'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'ids.array' => 'معرفات العروض يجب أن تكون مصفوفة',
            'ids.*.uuid' => 'معرف العرض يجب أن يكون UUID صحيح',
            'ids.*.exists' => 'العرض المحدد غير موجود',
            'format.string' => 'صيغة التصدير يجب أن تكون نص',
            'format.in' => 'صيغة التصدير يجب أن تكون xlsx أو csv',
            'search.string' => 'البحث يجب أن يكون نص',
            'search.max' => 'البحث يجب ألا يتجاوز 255 حرف',
            'name.string' => 'اسم العرض يجب أن يكون نص',
            'name.max' => 'اسم العرض يجب ألا يتجاوز 255 حرف',
            'company_id.uuid' => 'معرف الشركة يجب أن يكون UUID صحيح',
            'company_id.exists' => 'الشركة المحددة غير موجودة',
            'company_ids.array' => 'معرفات الشركات يجب أن تكون مصفوفة',
            'company_ids.*.uuid' => 'معرف الشركة يجب أن يكون UUID صحيح',
            'company_ids.*.exists' => 'الشركة المحددة غير موجودة',
            'product_id.uuid' => 'معرف المنتج يجب أن يكون UUID صحيح',
            'product_id.exists' => 'المنتج المحدد غير موجود',
            'product_ids.array' => 'معرفات المنتجات يجب أن تكون مصفوفة',
            'product_ids.*.uuid' => 'معرف المنتج يجب أن يكون UUID صحيح',
            'product_ids.*.exists' => 'المنتج المحدد غير موجود',
            'discount_type.string' => 'نوع الخصم يجب أن يكون نص',
            'discount_type.in' => 'نوع الخصم يجب أن يكون نسبة مئوية أو مبلغ ثابت',
            'discount_types.array' => 'أنواع الخصم يجب أن تكون مصفوفة',
            'discount_types.*.string' => 'نوع الخصم يجب أن يكون نص',
            'discount_types.*.in' => 'نوع الخصم يجب أن يكون نسبة مئوية أو مبلغ ثابت',
            'min_discount_value.numeric' => 'الحد الأدنى لقيمة الخصم يجب أن يكون رقم',
            'min_discount_value.min' => 'الحد الأدنى لقيمة الخصم يجب أن يكون 0 أو أكثر',
            'max_discount_value.numeric' => 'الحد الأقصى لقيمة الخصم يجب أن يكون رقم',
            'max_discount_value.min' => 'الحد الأقصى لقيمة الخصم يجب أن يكون 0 أو أكثر',
            'is_active.boolean' => 'حالة النشاط يجب أن تكون صحيح أو خطأ',
            'active_only.boolean' => 'النشط فقط يجب أن يكون صحيح أو خطأ',
            'inactive_only.boolean' => 'غير النشط فقط يجب أن يكون صحيح أو خطأ',
            'created_from.date' => 'تاريخ الإنشاء من يجب أن يكون تاريخ صحيح',
            'created_to.date' => 'تاريخ الإنشاء إلى يجب أن يكون تاريخ صحيح',
            'created_to.after_or_equal' => 'تاريخ الإنشاء إلى يجب أن يكون بعد أو يساوي تاريخ الإنشاء من',
            'updated_from.date' => 'تاريخ التحديث من يجب أن يكون تاريخ صحيح',
            'updated_to.date' => 'تاريخ التحديث إلى يجب أن يكون تاريخ صحيح',
            'updated_to.after_or_equal' => 'تاريخ التحديث إلى يجب أن يكون بعد أو يساوي تاريخ التحديث من',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the filters from the request
     */
    public function getFilters(): array
    {
        return array_filter([
            'search' => $this->input('search'),
            'name' => $this->input('name'),
            'company_id' => $this->input('company_id'),
            'product_id' => $this->input('product_id'),
            'discount_type' => $this->input('discount_type'),
            'min_discount_value' => $this->input('min_discount_value'),
            'max_discount_value' => $this->input('max_discount_value'),
            'is_active' => $this->input('is_active'),
            'active_only' => $this->input('active_only'),
            'inactive_only' => $this->input('inactive_only'),
            'created_from' => $this->input('created_from'),
            'created_to' => $this->input('created_to'),
            'updated_from' => $this->input('updated_from'),
            'updated_to' => $this->input('updated_to'),
        ], function ($value) {
            return $value !== null && $value !== '';
        });
    }
}
