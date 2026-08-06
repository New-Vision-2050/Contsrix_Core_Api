<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSubEntityRecordAttendanceStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_user_id' => ['required', 'uuid', 'exists:company_users,id'],
            'work_date' => ['nullable', 'date', 'required_without:date_from'],
            'date_from' => ['nullable', 'date', 'required_without:work_date'],
            'date_to' => ['nullable', 'date'],
            'status' => ['required', 'string', Rule::in(['holiday', 'required_attendance'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $dateFrom = $this->input('date_from') ?: $this->input('work_date');
            $dateTo = $this->input('date_to') ?: $dateFrom;

            if ($dateFrom && $dateTo && strtotime((string) $dateTo) < strtotime((string) $dateFrom)) {
                $validator->errors()->add('date_to', 'The date to must be after or equal to date from.');
            }
        });
    }
}
