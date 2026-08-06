<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\CompanyUser\Commands\UpdateIdentityDataCommand;

class AttendanceAttachmentRequest extends FormRequest
{
    public function rules(): array
    {
        $key = $this->input('key');

        $rules = [
            'key' => ['required', 'string', Rule::in(['profile', 'passport', 'identity', 'border_number'])],
        ];

        return array_merge($rules, match ($key) {
            'profile' => [
                'image' => 'required|file',
            ],
            'passport' => [
                'passport' => 'nullable|string',
                'passport_start_date' => 'nullable',
                'passport_end_date' => 'required_with:passport_start_date|date|after:passport_start_date',
                'file_passport.*' => 'nullable',
            ],
            'identity' => [
                'identity' => 'nullable|string|numeric',
                'identity_start_date' => 'nullable',
                'identity_end_date' => 'required_with:identity_start_date|date|after:identity_start_date',
                'file_identity.*' => 'nullable',
            ],
            'border_number' => [
                'border_number' => 'nullable|string|numeric',
                'border_number_start_date' => 'nullable',
                'border_number_end_date' => 'nullable|date|after:border_number_start_date',
                'file_border_number.*' => 'nullable',
            ],
            default => [],
        });
    }

    public function messages(): array
    {
        return [
            'passport_end_date.required_with' => __('validation.identity.passport_end_date_required_with'),
            'passport_end_date.date' => __('validation.identity.passport_end_date_date'),
            'passport_end_date.after' => __('validation.identity.passport_end_date_after'),

            'identity_end_date.required_with' => __('validation.identity.identity_end_date_required_with'),
            'identity_end_date.date' => __('validation.identity.identity_end_date_date'),
            'identity_end_date.after' => __('validation.identity.identity_end_date_after'),

            'border_number_end_date.date' => __('validation.identity.border_number_end_date_date'),
            'border_number_end_date.after' => __('validation.identity.border_number_end_date_after'),
        ];
    }

    public function updateIdentityDataCommand(): UpdateIdentityDataCommand
    {
        return new UpdateIdentityDataCommand(
            passport: $this->get('passport'),
            identity: $this->get('identity'),
            border_number: $this->get('border_number'),
            entry_number: null,
            passport_start_date: $this->get('passport_start_date'),
            identity_start_date: $this->get('identity_start_date'),
            border_number_start_date: $this->get('border_number_start_date'),
            entry_number_start_date: null,
            passport_end_date: $this->get('passport_end_date'),
            identity_end_date: $this->get('identity_end_date'),
            border_number_end_date: $this->get('border_number_end_date'),
            entry_number_end_date: null,
            work_permit_start_date: null,
            work_permit_end_date: null,
            work_permit: null,
        );
    }
}
