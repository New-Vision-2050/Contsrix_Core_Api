<?php

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SafetyAnalyticsDateRangeRequest extends FormRequest
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
            'contractor_id' => ['sometimes', 'uuid', 'exists:project_contractors,id'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
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

    public function contractorId(): ?string
    {
        $id = $this->validated('contractor_id') ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function limit(int $default = 5): int
    {
        return (int) ($this->validated('limit') ?? $default);
    }
}
