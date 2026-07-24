<?php

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EvaluateViolationsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'violations' => ['required', 'array', 'min:1'],
            'violations.*.violation_id' => ['required', 'uuid', 'exists:violations,id'],
            'violations.*.weight'       => ['nullable', 'numeric'],
            'violations.*.status'       => ['required', 'in:violation_found,no_violation,not_applicable'],
        ];
    }
}
