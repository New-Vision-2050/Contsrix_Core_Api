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
            'main_program_slug' => ['nullable', 'string', 'exists:programs,slug'],
            'entity_name' => ['nullable', 'string', 'exists:sub_entities,name'],
            'registration_form_id' => ['nullable', 'string', 'exists:registration_forms,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
