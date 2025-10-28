<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportSubEntityRecordsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'format' => ['sometimes', Rule::in(['xlsx', 'csv'])],
//            'sub_entity_id' => ['required', 'string', 'exists:sub_entities,id'],
//            'registration_form_id' => ['required', 'string', 'exists:registration_forms,id'],
            'branch_id' => ['nullable', 'exists:management_hierarchies,id,type,branch'],
            'ids' => ['sometimes', 'array'],
            'ids.*' => ['uuid'],
        ];
    }

    /**
     * Get filters from the request
     *
     * @return array
     */
    public function getFilters(): array
    {
        return array_filter([
            'sub_entity_id' => $this->get('sub_entity_id'),
            'registration_form_id' => $this->get('registration_form_id'),
            'branch_id' => $this->get('branch_id'),
            'ids' => $this->get('ids'),
        ]);
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'format.in' => 'The format must be either xlsx or csv.',
            'sub_entity_id.required' => 'The sub entity ID is required.',
            'sub_entity_id.exists' => 'The selected sub entity does not exist.',
            'registration_form_id.required' => 'The registration form ID is required.',
            'registration_form_id.exists' => 'The selected registration form does not exist.',
            'branch_id.exists' => 'The selected branch does not exist.',
            'ids.array' => 'The IDs must be an array.',
            'ids.*.uuid' => 'Each ID must be a valid UUID.',
        ];
    }
}
