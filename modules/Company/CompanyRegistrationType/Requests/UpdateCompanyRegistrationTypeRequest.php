<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyRegistrationType\Commands\UpdateCompanyRegistrationTypeCommand;
use Modules\Company\CompanyRegistrationType\Handlers\UpdateCompanyRegistrationTypeHandler;

class UpdateCompanyRegistrationTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'type' => 'required|numeric',
        ];
    }

    public function createUpdateCompanyRegistrationTypeCommand(): UpdateCompanyRegistrationTypeCommand
    {
        return new UpdateCompanyRegistrationTypeCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            type:$this->get('type'),
        );
    }
}
