<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetTermSettingChildrenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:term_settings,id',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }
}
