<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectSharingWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_type_id' => ['sometimes', 'integer', 'exists:project_types,id'],
            'code'            => ['sometimes', 'nullable', 'string', 'max:255'],
            'description'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'type'            => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
