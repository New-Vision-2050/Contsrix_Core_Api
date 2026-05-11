<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderPermitTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_type_id' => ['required', 'integer', 'exists:project_types,id'],
            'code'            => ['nullable', 'string', 'max:255'],
            'name'            => ['nullable', 'string', 'max:255'],
        ];
    }
}
