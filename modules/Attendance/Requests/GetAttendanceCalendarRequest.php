<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetAttendanceCalendarRequest extends FormRequest
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
            'from_date' => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d'],
            'to_date'   => ['sometimes', 'nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'month'     => ['sometimes', 'nullable', 'integer', 'min:1', 'max:12'],
            'year'      => ['sometimes', 'nullable', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'from_date' => 'from date',
            'to_date'   => 'to date',
            'month'     => 'month',
            'year'      => 'year',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'from_date.date'          => 'The from date must be a valid date.',
            'from_date.date_format'   => 'The from date must be in Y-m-d format.',
            'to_date.date'            => 'The to date must be a valid date.',
            'to_date.date_format'     => 'The to date must be in Y-m-d format.',
            'to_date.after_or_equal'  => 'The to date must be after or equal to the from date.',
            'month.integer'           => 'The month must be a number between 1 and 12.',
            'month.min'               => 'The month must be at least 1.',
            'month.max'               => 'The month must not exceed 12.',
            'year.integer'            => 'The year must be a number.',
            'year.min'                => 'The year must be at least 2000.',
            'year.max'                => 'The year must not exceed 2100.',
        ];
    }
}
