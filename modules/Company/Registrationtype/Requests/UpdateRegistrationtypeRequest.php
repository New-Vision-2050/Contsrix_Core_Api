<?php

declare(strict_types=1);

namespace Modules\Company\RegistrationType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\RegistrationType\Commands\UpdateRegistrationTypeCommand;
use Modules\Company\RegistrationType\Handlers\UpdateRegistrationTypeHandler;

class UpdateRegistrationTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateRegistrationTypeCommand(): UpdateRegistrationTypeCommand
    {
        return new UpdateRegistrationTypeCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
