<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
