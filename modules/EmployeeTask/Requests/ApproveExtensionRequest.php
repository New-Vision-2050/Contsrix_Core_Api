<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation request for approving extension requests.
 * 
 * Ensures that admin-provided data meets business requirements
 * before being passed to the service layer.
 */
class ApproveExtensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approval_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'approval_notes.string' => 'Approval notes must be a valid text string.',
            'approval_notes.max'    => 'Approval notes cannot exceed 1000 characters.',
        ];
    }
}
