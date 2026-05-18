<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateExtensionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'additional_hours' => ['required', 'numeric', 'min:0.25', 'max:24'],
            'reason'           => ['nullable', 'string', 'max:1000'],
        ];
    }
}
