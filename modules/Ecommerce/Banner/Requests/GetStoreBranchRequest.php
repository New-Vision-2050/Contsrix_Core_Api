<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetStoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|uuid|exists:store_branches,id',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'معرف الفرع مطلوب',
            'id.uuid' => 'معرف الفرع يجب أن يكون UUID صحيح',
            'id.exists' => 'الفرع المحدد غير موجود',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }
}
