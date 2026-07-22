<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectOrderPermitStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_completion_phase_id' => ['nullable', 'integer', 'exists:project_completion_phases,id'],
            'project_phase_status_id' => ['nullable', 'integer', 'exists:project_phase_statuses,id'],
            'connection_completion_phase_id' => ['nullable', 'integer', 'exists:connection_completion_phases,id'],
            'connection_phase_status_id' => ['nullable', 'integer', 'exists:connection_phase_statuses,id'],
        ];
    }
}
