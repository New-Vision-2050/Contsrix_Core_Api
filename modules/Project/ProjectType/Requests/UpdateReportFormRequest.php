<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReportFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_type_id'               => ['sometimes', 'integer', 'exists:project_types,id'],
            'project_sharing_work_order_id' => ['sometimes', 'integer', 'exists:project_sharing_work_orders,id'],
            'name'                          => ['sometimes', 'nullable', 'string', 'max:255'],
            'question'                      => ['sometimes', 'nullable', 'string', 'max:255'],
            'value'                         => ['sometimes', 'nullable', 'string', 'max:255'],
            'number_of_attachments'         => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes'                         => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
