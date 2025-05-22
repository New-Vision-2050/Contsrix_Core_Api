<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\SubEntity\Commands\UpdateSuperEntityRegistrationFormsConfigCommand;

class UpdateSuperEntityRegistrationFormsConfigRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'registration_forms' => 'required|array',
            'registration_forms.*' => 'exists:registration_forms,id',
        ];
    }

    public function createUpdateSuperEntityRegistrationConfigCommand(): UpdateSuperEntityRegistrationFormsConfigCommand
    {
        return new UpdateSuperEntityRegistrationFormsConfigCommand(
            id: $this->route('id'),
            registrationForms: $this->input('registration_forms'),
        );
    }
}
