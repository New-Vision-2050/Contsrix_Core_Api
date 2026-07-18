<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderPermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'                        => ['nullable', 'string', 'max:255'],
            'description'                 => ['nullable', 'string', 'max:255'],
            'type'                        => ['nullable', 'string', 'max:255'],
            'uds_period'                  => ['nullable', 'numeric'],
            'order_permit_department_id'  => ['nullable', 'integer', 'exists:order_permit_department,id'],
        ];
    }
}
