<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertPageByTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description' => 'required|array',
            'description.ar' => 'required|string',
            'description.en' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'الوصف مطلوب',
            'description.array' => 'الوصف يجب أن يكون مصفوفة تحتوي على اللغات',
            'description.ar.required' => 'الوصف باللغة العربية مطلوب',
            'description.ar.string' => 'الوصف باللغة العربية يجب أن يكون نص',
            'description.en.required' => 'الوصف باللغة الإنجليزية مطلوب',
            'description.en.string' => 'الوصف باللغة الإنجليزية يجب أن يكون نص',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
