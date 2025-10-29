<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchDealDayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'company_id' => 'nullable|string|exists:companies,id',
            'product_id' => 'nullable|string|exists:eco_products,id',
            'discount_type' => 'nullable|string|in:percentage,fixed',
            'min_discount_value' => 'nullable|numeric|min:0',
            'max_discount_value' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'active_only' => 'nullable|boolean',
            'inactive_only' => 'nullable|boolean',
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date|after_or_equal:created_from',
            'updated_from' => 'nullable|date',
            'updated_to' => 'nullable|date|after_or_equal:updated_from',
            'order_by' => 'nullable|string|in:name,discount_value,created_at',
            'order_direction' => 'nullable|string|in:asc,desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'search.string' => 'يجب أن يكون البحث نص صالح',
            'search.max' => 'يجب ألا يتجاوز البحث 255 حرف',
            'name.string' => 'يجب أن يكون الاسم نص صالح',
            'name.max' => 'يجب ألا يتجاوز الاسم 255 حرف',
            'company_id.string' => 'يجب أن يكون معرف الشركة نص صالح',
            'company_id.exists' => 'الشركة المحددة غير موجودة',
            'product_id.string' => 'يجب أن يكون معرف المنتج نص صالح',
            'product_id.exists' => 'المنتج المحدد غير موجود',
            'discount_type.string' => 'يجب أن يكون نوع الخصم نص صالح',
            'discount_type.in' => 'نوع الخصم يجب أن يكون نسبة مئوية أو مبلغ ثابت',
            'min_discount_value.numeric' => 'يجب أن يكون الحد الأدنى لقيمة الخصم رقم صالح',
            'min_discount_value.min' => 'يجب أن يكون الحد الأدنى لقيمة الخصم أكبر من أو يساوي صفر',
            'max_discount_value.numeric' => 'يجب أن يكون الحد الأقصى لقيمة الخصم رقم صالح',
            'max_discount_value.min' => 'يجب أن يكون الحد الأقصى لقيمة الخصم أكبر من أو يساوي صفر',
            'is_active.boolean' => 'يجب أن تكون حالة النشاط صحيح أو خطأ',
            'active_only.boolean' => 'يجب أن يكون النشط فقط صحيح أو خطأ',
            'inactive_only.boolean' => 'يجب أن يكون غير النشط فقط صحيح أو خطأ',
            'created_from.date' => 'يجب أن يكون تاريخ الإنشاء من تاريخ صالح',
            'created_to.date' => 'يجب أن يكون تاريخ الإنشاء إلى تاريخ صالح',
            'created_to.after_or_equal' => 'يجب أن يكون تاريخ الإنشاء إلى بعد أو يساوي تاريخ الإنشاء من',
            'updated_from.date' => 'يجب أن يكون تاريخ التحديث من تاريخ صالح',
            'updated_to.date' => 'يجب أن يكون تاريخ التحديث إلى تاريخ صالح',
            'updated_to.after_or_equal' => 'يجب أن يكون تاريخ التحديث إلى بعد أو يساوي تاريخ التحديث من',
            'order_by.string' => 'يجب أن يكون ترتيب حسب نص صالح',
            'order_by.in' => 'ترتيب حسب يجب أن يكون الاسم أو قيمة الخصم أو تاريخ الإنشاء',
            'order_direction.string' => 'يجب أن يكون اتجاه الترتيب نص صالح',
            'order_direction.in' => 'اتجاه الترتيب يجب أن يكون تصاعدي أو تنازلي',
            'page.integer' => 'يجب أن تكون الصفحة رقم صحيح',
            'page.min' => 'يجب أن تكون الصفحة أكبر من أو تساوي 1',
            'per_page.integer' => 'يجب أن يكون عدد العناصر في الصفحة رقم صحيح',
            'per_page.min' => 'يجب أن يكون عدد العناصر في الصفحة أكبر من أو يساوي 1',
            'per_page.max' => 'يجب ألا يتجاوز عدد العناصر في الصفحة 100',
        ];
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
            'order_by' => $this->input('order_by'),
            'order_direction' => $this->input('order_direction'),
        ], function ($value) {
            return $value !== null && $value !== '';
        });
    }
}
