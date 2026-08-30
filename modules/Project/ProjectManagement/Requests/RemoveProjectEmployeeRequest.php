<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoveProjectEmployeeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:project_employees,id'],
        ];
    }

    public function projectEmployeeId(): string
    {
        return $this->validated('id');
    }
}
