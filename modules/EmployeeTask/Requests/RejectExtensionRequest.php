<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation request for rejecting extension requests.
 * 
 * Ensures that admin-provided rejection data meets business requirements
 * before being passed to the service layer.
 */
class RejectExtensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required' => 'A rejection reason is required.',
            'rejection_reason.string'   => 'Rejection reason must be a valid text string.',
            'rejection_reason.min'      => 'Rejection reason must be at least 10 characters long.',
            'rejection_reason.max'      => 'Rejection reason cannot exceed 1000 characters.',
        ];
    }
}
