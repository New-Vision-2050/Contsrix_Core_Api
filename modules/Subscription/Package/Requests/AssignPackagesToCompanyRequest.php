<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignPackagesToCompanyRequest extends FormRequest
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
            'company_id' => 'required|uuid|exists:companies,id',
            'package_ids' => 'required|array|min:1',
            'package_ids.*' => 'required|uuid|exists:packages,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'company_id.required' => 'Company ID is required.',
            'company_id.uuid' => 'Company ID must be a valid UUID.',
            'company_id.exists' => 'The selected company does not exist.',
            'package_ids.required' => 'At least one package ID is required.',
            'package_ids.array' => 'Package IDs must be an array.',
            'package_ids.min' => 'At least one package must be selected.',
            'package_ids.*.required' => 'Each package ID is required.',
            'package_ids.*.uuid' => 'Each package ID must be a valid UUID.',
            'package_ids.*.exists' => 'One or more selected packages do not exist.',
        ];
    }
}
