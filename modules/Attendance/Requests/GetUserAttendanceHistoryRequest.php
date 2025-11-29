<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetUserAttendanceHistoryRequest extends FormRequest
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
            'month' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['sometimes', 'nullable', 'integer', 'min:2000', 'max:2100'],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'month' => 'month',
            'year' => 'year',
            'page' => 'page',
            'per_page' => 'per page',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'month.integer' => 'The month must be a number between 1 and 12.',
            'month.min' => 'The month must be at least 1.',
            'month.max' => 'The month must not exceed 12.',
            'year.integer' => 'The year must be a number.',
            'year.min' => 'The year must be at least 2000.',
            'year.max' => 'The year must not exceed 2100.',
            'page.integer' => 'The page must be a number.',
            'page.min' => 'The page must be at least 1.',
            'per_page.integer' => 'The per page must be a number.',
            'per_page.min' => 'The per page must be at least 1.',
            'per_page.max' => 'The per page must not exceed 100.',
        ];
    }
}

