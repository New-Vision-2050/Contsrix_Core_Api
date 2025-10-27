<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetFeatureRequest extends FormRequest
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
            'id.required' => 'معرف الميزة مطلوب',
            'id.uuid' => 'معرف الميزة يجب أن يكون UUID صحيح',
        ];
    }
}
