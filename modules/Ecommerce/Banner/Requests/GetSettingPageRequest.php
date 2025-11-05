<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetSettingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'معرف إعدادات الصفحة مطلوب',
            'id.uuid' => 'معرف إعدادات الصفحة يجب أن يكون UUID صحيح',
        ];
    }
}
