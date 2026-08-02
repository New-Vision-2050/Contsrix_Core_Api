<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubEntityRecordAttendanceStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_user_id' => ['required', 'uuid', 'exists:company_users,id'],
            'status' => ['required', 'string', Rule::in(['holiday', 'required_attendance'])],
        ];
    }
}
