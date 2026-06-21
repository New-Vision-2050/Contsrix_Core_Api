<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetStatusInternalProcedureSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function isActive(): bool
    {
        return (bool) $this->validated('is_active');
    }
}
