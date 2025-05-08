<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\Commands\UpdateIdentityDataCommand;

class IdentityDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'passport' => 'nullable|string',
            'identity' => 'nullable|string',
            'border_number' => 'nullable|string',
            'entry_number' => 'nullable|string',

            'passport_start_date'=>'nullable',
            'identity_start_date'=>'nullable',
            'border_number_start_date'=>'nullable',
            'entry_number_start_date'=>'nullable',

            'passport_end_date' => 'required_with:passport_start_date|date|after:passport_start_date',
            'identity_end_date' => 'required_with:identity_start_date|date|after:identity_start_date',
            'border_number_end_date' => 'required_with:border_number_start_date|date|after:border_number_start_date',
            'entry_number_end_date' => 'required_with:entry_number_start_date|date|after:entry_number_start_date',

            'file_passport.*' => 'nullable',
            'file_identity.*' => 'nullable',
            'file_border_number.*' => 'nullable',
            'file_entry_number.*' => 'nullable',
            'file_work_permit.*' => 'nullable',

            'work_permit_start_date'=>'nullable|string',
            'work_permit_end_date' => 'required_with:work_permit_start_date|date|after:work_permit_start_date',
            'work_permit' => 'nullable',
        ];
    }

    public function updateIdentityDataCommand(): UpdateIdentityDataCommand
    {
        return new UpdateIdentityDataCommand(
            passport: $this->get('passport'),
            identity: $this->get('identity'),
            border_number: $this->get('border_number'),
            entry_number: $this->get('entry_number'),
            passport_start_date: $this->get('passport_start_date'),
            identity_start_date: $this->get('identity_start_date'),
            border_number_start_date: $this->get('border_number_start_date'),
            entry_number_start_date: $this->get('entry_number_start_date'),

            passport_end_date: $this->get('passport_end_date'),
            identity_end_date: $this->get('identity_end_date'),
            border_number_end_date: $this->get('border_number_end_date'),
            entry_number_end_date: $this->get('entry_number_end_date'),

            work_permit_start_date:$this->get('work_permit_start_date'),
            work_permit_end_date:$this->get('work_permit_end_date'),
            work_permit:$this->get('work_permit'),
        );
    }
}
