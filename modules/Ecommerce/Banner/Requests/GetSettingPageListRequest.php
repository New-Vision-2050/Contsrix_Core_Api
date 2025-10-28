<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetSettingPageListRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'type' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
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
            'type.string' => 'نوع الصفحة يجب أن يكون نص',
            'type.max' => 'نوع الصفحة يجب ألا يتجاوز 255 حرف',
            'is_active.boolean' => 'حالة النشاط يجب أن تكون صحيح أو خطأ',
            'search.string' => 'البحث يجب أن يكون نص',
            'search.max' => 'البحث يجب ألا يتجاوز 255 حرف',
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
