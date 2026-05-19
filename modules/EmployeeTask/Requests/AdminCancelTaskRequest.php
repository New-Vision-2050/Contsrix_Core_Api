<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminCancelTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
