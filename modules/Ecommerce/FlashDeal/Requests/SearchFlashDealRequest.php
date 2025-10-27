<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchFlashDealRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
            'is_active' => ['nullable', 'boolean'],
            'active_only' => ['nullable', 'boolean'],
            'inactive_only' => ['nullable', 'boolean'],
            'currently_active_only' => ['nullable', 'boolean'],
            'upcoming_only' => ['nullable', 'boolean'],
            'expired_only' => ['nullable', 'boolean'],
            'start_date_from' => ['nullable', 'date'],
            'start_date_to' => ['nullable', 'date|after_or_equal:start_date_from'],
            'end_date_from' => ['nullable', 'date'],
            'end_date_to' => ['nullable', 'date|after_or_equal:end_date_from'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date|after_or_equal:created_from'],
            'updated_from' => ['nullable', 'date'],
            'updated_to' => ['nullable', 'date|after_or_equal:updated_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'search.string' => 'البحث يجب أن يكون نص',
            'search.max' => 'البحث يجب ألا يتجاوز 255 حرف',
            'name.string' => 'اسم العرض يجب أن يكون نص',
            'name.max' => 'اسم العرض يجب ألا يتجاوز 255 حرف',
            'company_id.uuid' => 'معرف الشركة يجب أن يكون UUID صحيح',
            'company_id.exists' => 'الشركة المحددة غير موجودة',
            'is_active.boolean' => 'حالة النشاط يجب أن تكون صحيح أو خطأ',
            'active_only.boolean' => 'النشط فقط يجب أن يكون صحيح أو خطأ',
            'inactive_only.boolean' => 'غير النشط فقط يجب أن يكون صحيح أو خطأ',
            'currently_active_only.boolean' => 'النشط حالياً فقط يجب أن يكون صحيح أو خطأ',
            'upcoming_only.boolean' => 'القادم فقط يجب أن يكون صحيح أو خطأ',
            'expired_only.boolean' => 'المنتهي فقط يجب أن يكون صحيح أو خطأ',
            'start_date_from.date' => 'تاريخ البداية من يجب أن يكون تاريخ صحيح',
            'start_date_to.date' => 'تاريخ البداية إلى يجب أن يكون تاريخ صحيح',
            'start_date_to.after_or_equal' => 'تاريخ البداية إلى يجب أن يكون بعد أو يساوي تاريخ البداية من',
            'end_date_from.date' => 'تاريخ النهاية من يجب أن يكون تاريخ صحيح',
            'end_date_to.date' => 'تاريخ النهاية إلى يجب أن يكون تاريخ صحيح',
            'end_date_to.after_or_equal' => 'تاريخ النهاية إلى يجب أن يكون بعد أو يساوي تاريخ النهاية من',
            'created_from.date' => 'تاريخ الإنشاء من يجب أن يكون تاريخ صحيح',
            'created_to.date' => 'تاريخ الإنشاء إلى يجب أن يكون تاريخ صحيح',
            'created_to.after_or_equal' => 'تاريخ الإنشاء إلى يجب أن يكون بعد أو يساوي تاريخ الإنشاء من',
            'updated_from.date' => 'تاريخ التحديث من يجب أن يكون تاريخ صحيح',
            'updated_to.date' => 'تاريخ التحديث إلى يجب أن يكون تاريخ صحيح',
            'updated_to.after_or_equal' => 'تاريخ التحديث إلى يجب أن يكون بعد أو يساوي تاريخ التحديث من',
            'page.integer' => 'رقم الصفحة يجب أن يكون رقم صحيح',
            'page.min' => 'رقم الصفحة يجب أن يكون 1 أو أكثر',
            'per_page.integer' => 'عدد العناصر في الصفحة يجب أن يكون رقم صحيح',
            'per_page.min' => 'عدد العناصر في الصفحة يجب أن يكون 1 أو أكثر',
            'per_page.max' => 'عدد العناصر في الصفحة يجب ألا يتجاوز 100',
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
            'is_active' => $this->input('is_active'),
            'active_only' => $this->input('active_only'),
            'inactive_only' => $this->input('inactive_only'),
            'currently_active_only' => $this->input('currently_active_only'),
            'upcoming_only' => $this->input('upcoming_only'),
            'expired_only' => $this->input('expired_only'),
            'start_date_from' => $this->input('start_date_from'),
            'start_date_to' => $this->input('start_date_to'),
            'end_date_from' => $this->input('end_date_from'),
            'end_date_to' => $this->input('end_date_to'),
            'created_from' => $this->input('created_from'),
            'created_to' => $this->input('created_to'),
            'updated_from' => $this->input('updated_from'),
            'updated_to' => $this->input('updated_to'),
        ], function ($value) {
            return $value !== null && $value !== '';
        });
    }
}
