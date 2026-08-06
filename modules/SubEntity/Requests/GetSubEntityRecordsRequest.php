<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GetSubEntityRecordsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sub_entity_id' => ['required', 'string', 'exists:sub_entities,id'],
            'registration_form_id' => ['required', 'string', 'exists:registration_forms,id'],
            'branch_id' => ['nullable', 'exists:management_hierarchies,id,type,branch'],
            'start_date' => ['nullable', 'date'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $dateFrom = $this->input('date_from') ?: $this->input('start_date') ?: $this->input('date_to');
            $dateTo = $this->input('date_to') ?: $dateFrom;

            if ($dateFrom && $dateTo && strtotime((string) $dateTo) < strtotime((string) $dateFrom)) {
                $validator->errors()->add('date_to', 'The date to must be after or equal to date from.');
            }
        });
    }
}
