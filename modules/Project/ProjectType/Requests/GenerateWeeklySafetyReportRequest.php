<?php

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateWeeklySafetyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => ['required', 'date', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ];
    }

    public function fromDate(): string
    {
        return (string) $this->validated('from_date');
    }

    public function toDate(): string
    {
        return (string) $this->validated('to_date');
    }
}
