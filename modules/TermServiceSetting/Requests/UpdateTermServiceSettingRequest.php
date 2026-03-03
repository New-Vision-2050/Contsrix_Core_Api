<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\TermServiceSetting\DTO\UpdateTermServiceSettingDTO;

class UpdateTermServiceSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'term_setting_ids' => 'required|array|min:1',
            'term_setting_ids.*' => 'required|integer|exists:term_settings,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'term_setting_ids.required' => 'At least one term setting must be selected.',
            'term_setting_ids.array' => 'Term settings must be an array.',
            'term_setting_ids.min' => 'At least one term setting must be selected.',
            'term_setting_ids.*.required' => 'Each term setting ID is required.',
            'term_setting_ids.*.integer' => 'Each term setting ID must be an integer.',
            'term_setting_ids.*.exists' => 'The selected term setting does not exist.',
        ];
    }

    public function createUpdateTermServiceSettingDTO(): UpdateTermServiceSettingDTO
    {
        return new UpdateTermServiceSettingDTO(
            id: (int) $this->route('id'),
            name: $this->get('name'),
            termSettingIds: $this->get('term_setting_ids', []),
        );
    }
}
