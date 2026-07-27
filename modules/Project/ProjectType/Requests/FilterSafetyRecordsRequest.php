<?php

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }

    /**
     * Validated filter bag with empty values removed for service `when()` checks.
     *
     * @return array{
     *     search?: string,
     *     date?: string,
     *     consultant_engineer?: string,
     *     consultant?: string,
     *     contractor_id?: string,
     *     assigned_user_id?: string
     * }
     */
    public function filters(): array
    {
        return array_filter(
            $this->validated(),
            fn ($value) => $value !== null && $value !== ''
        );
    }
}
