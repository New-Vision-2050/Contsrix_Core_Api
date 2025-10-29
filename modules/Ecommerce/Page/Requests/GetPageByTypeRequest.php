<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetPageByTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:terms_conditions,privacy_policy,refund_policy,return_policy,cancellation_policy,shipping_policy,about_us,company_reliability',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'نوع الصفحة مطلوب',
            'type.string' => 'نوع الصفحة يجب أن يكون نص',
            'type.in' => 'نوع الصفحة يجب أن يكون أحد القيم المحددة',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
