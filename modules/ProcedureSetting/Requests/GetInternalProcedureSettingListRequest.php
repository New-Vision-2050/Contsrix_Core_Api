<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;

class GetInternalProcedureSettingListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', Rule::in(ProcedureSettingType::values())],
        ];
    }

    public function getType(): ?string
    {
        return $this->filled('type') ? (string) $this->get('type') : null;
    }
}
