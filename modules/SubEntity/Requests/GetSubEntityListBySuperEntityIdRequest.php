<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\SubEntity\Rules\ValidSuperEntityId;

class GetSubEntityListBySuperEntityIdRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'super_entity_id' => ['required', 'string', new ValidSuperEntityId()],
            'main_program_id' => ['required', 'string', 'exists:programs,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
