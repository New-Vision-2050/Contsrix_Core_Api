<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateReportFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_type_id'                => ['required', 'integer', 'exists:project_types,id'],
            'order_permit_procedure_id'      => ['required', 'integer', 'exists:order_permit_procedure,id'],
            'name'                           => ['nullable', 'string', 'max:255'],
            'question'                       => ['nullable', 'string', 'max:255'],
            'value'                          => ['nullable', 'string', 'max:255'],
            'number_of_attachments'          => ['nullable', 'string', 'max:255'],
            'notes'                          => ['nullable', 'string', 'max:255'],
        ];
    }
}
