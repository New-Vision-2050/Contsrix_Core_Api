<?php

declare(strict_types=1);

namespace Modules\Setting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array', 'min:1'],
            'settings.*.key' => ['required', 'string', 'max:255'],
            'settings.*.value' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.required' => 'Settings array is required',
            'settings.array' => 'Settings must be an array',
            'settings.min' => 'At least one setting is required',
            'settings.*.key.required' => 'Each setting must have a key',
            'settings.*.key.string' => 'Setting key must be a string',
            'settings.*.key.max' => 'Setting key must not exceed 255 characters',
            'settings.*.value.required' => 'Each setting must have a value',
        ];
    }

    public function getSettings(): array
    {
        return $this->input('settings', []);
    }
}
