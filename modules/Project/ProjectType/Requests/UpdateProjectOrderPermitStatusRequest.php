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
            'completion_phase_id' => ['nullable', 'integer'],
            'phase_status_id' => ['nullable', 'integer'],
        ];
    }
}
