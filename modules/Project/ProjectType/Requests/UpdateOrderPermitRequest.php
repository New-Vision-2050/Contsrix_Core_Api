<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderPermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'                        => ['sometimes', 'nullable', 'string', 'max:255'],
            'description'                 => ['sometimes', 'nullable', 'string', 'max:255'],
            'type'                        => ['sometimes', 'nullable', 'string', 'max:255'],
            'uds_period'                  => ['sometimes', 'nullable', 'numeric'],
            'order_permit_department_id'  => ['sometimes', 'nullable', 'integer', 'exists:order_permit_department,id'],
            'order_permit_type_id'        => ['sometimes', 'nullable', 'integer', 'exists:order_permit_types,id'],
        ];
    }
}
