<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\SubEntity\Commands\UpdateSuperEntityRegistrableConfigCommand;

class UpdateSuperEntityRegistrableConfigRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'is_registrable' => 'required|boolean',
        ];
    }

    public function createUpdateSuperEntityRegistrationConfigCommand(): UpdateSuperEntityRegistrableConfigCommand
    {
        return new UpdateSuperEntityRegistrableConfigCommand(
            id: $this->route('id'),
            registrable: (bool) $this->input('is_registrable'),
        );
    }
}
