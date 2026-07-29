<?php

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class FilterSafetyRecordsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'consultant_engineer' => ['nullable', 'string', 'max:255'],
            'consultant' => ['nullable', 'string', 'max:255'],
            'contractor_id' => ['nullable', 'uuid', 'exists:project_contractors,id'],
            'assigned_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'status' => ['nullable', 'string', 'in:pending,completed'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Validated filter bag with pagination keys and empty values removed.
     *
     * @return array{
     *     search?: string,
     *     date?: string,
     *     consultant_engineer?: string,
     *     consultant?: string,
     *     contractor_id?: string,
     *     assigned_user_id?: string,
     *     status?: string
     * }
     */
    public function filters(): array
    {
        return array_filter(
            Arr::except($this->validated(), ['per_page', 'page', 'sort']),
            fn ($value) => $value !== null && $value !== ''
        );
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 15);
    }

    public function sort(): ?string
    {
        $sort = $this->validated('sort') ?? null;

        return is_string($sort) && $sort !== '' ? $sort : null;
    }
}
