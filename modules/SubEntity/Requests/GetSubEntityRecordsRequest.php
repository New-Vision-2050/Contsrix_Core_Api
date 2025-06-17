<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetSubEntityRecordsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sub_entity_id' => ['required', 'string', 'exists:sub_entities,id'],
            'registration_form_id' => ['required', 'string', 'exists:registration_forms,id'],
            'branch_id' => ['nullable', 'exists:management_hierarchies,id,type,branch'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
