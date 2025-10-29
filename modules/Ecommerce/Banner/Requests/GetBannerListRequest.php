<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetBannerListRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'setting_page_id' => ['nullable', 'uuid', 'exists:setting_pages,id'],
            'type' => ['nullable', 'string', 'max:255'],
            'setting_page_type' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
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
            'page.integer' => 'رقم الصفحة يجب أن يكون رقم صحيح',
            'page.min' => 'رقم الصفحة يجب أن يكون 1 أو أكثر',
            'per_page.integer' => 'عدد العناصر في الصفحة يجب أن يكون رقم صحيح',
            'per_page.min' => 'عدد العناصر في الصفحة يجب أن يكون 1 أو أكثر',
            'per_page.max' => 'عدد العناصر في الصفحة يجب ألا يتجاوز 100',
            'setting_page_id.uuid' => 'معرف صفحة الإعدادات يجب أن يكون UUID صحيح',
            'setting_page_id.exists' => 'صفحة الإعدادات المحددة غير موجودة',
            'type.string' => 'نوع البانر يجب أن يكون نص',
            'type.max' => 'نوع البانر يجب ألا يتجاوز 255 حرف',
            'setting_page_type.string' => 'نوع صفحة الإعدادات يجب أن يكون نص',
            'setting_page_type.max' => 'نوع صفحة الإعدادات يجب ألا يتجاوز 255 حرف',
            'is_active.boolean' => 'حالة النشاط يجب أن تكون صحيح أو خطأ',
            'search.string' => 'البحث يجب أن يكون نص',
            'search.max' => 'البحث يجب ألا يتجاوز 255 حرف',
            'url.string' => 'الرابط يجب أن يكون نص',
            'url.max' => 'الرابط يجب ألا يتجاوز 255 حرف',
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
}
