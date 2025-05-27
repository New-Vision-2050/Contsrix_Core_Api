<?php

namespace Modules\SubEntity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\SubEntity\Rules\ValidSuperEntityId;

class ExportSubEntitiesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'ids' => 'nullable|array',
            'ids.*' => 'string|exists:sub_entities,id',
            'format' => 'nullable|string|in:xlsx,csv',
            'super_entity_id' => ['required', 'string', new ValidSuperEntityId()],
            'main_program_slug' => ['nullable', 'string', 'exists:programs,slug'],
            'entity_name' => ['nullable', 'string', 'exists:sub_entities,name'],
            'registration_form_id' => ['nullable', 'string', 'exists:registration_forms,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'format.in' => 'The format must be either xlsx or csv'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
