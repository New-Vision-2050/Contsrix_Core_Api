<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSubEntityRecordAttendanceStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_user_id' => ['required', 'uuid', 'exists:company_users,id'],
            'status' => ['required', 'string', Rule::in(['holiday', 'required_attendance'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            // Accepted aliases (normalized in prepareForValidation).
            'time_from' => ['nullable'],
            'time_to' => ['nullable'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $from = $this->input('date_from')
            ?? $this->input('time_from')
            ?? $this->input('start_date');
        $to = $this->input('date_to')
            ?? $this->input('time_to')
            ?? $this->input('end_date');

        $this->merge([
            'date_from' => $this->normalizeDateInput($from),
            'date_to' => $this->normalizeDateInput($to),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $from = $this->input('date_from');
            $to = $this->input('date_to');

            if (! is_string($from) || $from === '' || ! is_string($to) || $to === '') {
                return;
            }

            $days = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;

            if ($days > 366) {
                $validator->errors()->add('date_to', 'The holiday date range may not exceed 366 days.');
            }
        });
    }

    private function normalizeDateInput(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return is_string($value) ? $value : null;
        }
    }
}
