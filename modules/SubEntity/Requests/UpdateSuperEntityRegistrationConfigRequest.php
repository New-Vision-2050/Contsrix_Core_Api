<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\SubEntity\Rules\ValidSuperEntityId;
use Modules\SubEntity\Commands\UpdateSuperEntityRegistrationCommand;

class UpdateSuperEntityRegistrationConfigRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'registration_forms' => 'required|array',
            'registration_forms.*' => 'distinct|exists:registration_forms,id',
            'is_registrable' => 'required|boolean',
            'super_entity_id' => ['required', 'string', new ValidSuperEntityId()],
        ];
    }

    public function createUpdateSuperEntityRegistrationConfigCommand(): UpdateSuperEntityRegistrationCommand
    {
        return new UpdateSuperEntityRegistrationCommand(
            id: $this->input('super_entity_id'),
            registrationForms: $this->input('registration_forms'),
            isRegistrable: (bool) $this->input('is_registrable'),
        );
    }
}
