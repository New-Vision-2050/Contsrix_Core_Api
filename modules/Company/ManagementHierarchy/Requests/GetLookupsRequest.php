<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetLookupsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'job_type_ids' => 'nullable|array',
            'job_type_ids.*' => 'string|exists:job_types,id',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'job_type_ids' => 'Job Type IDs',
            'job_type_ids.*' => 'Job Type ID',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'job_type_ids.array' => 'Job type IDs must be an array.',
            'job_type_ids.*.string' => 'Each job type ID must be a string.',
            'job_type_ids.*.exists' => 'One or more job type IDs do not exist.',
        ];
    }
}
