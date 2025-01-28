<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationForm\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyRegistrationForm\Commands\UpdateCompanyRegistrationFormCommand;
use Modules\Company\CompanyRegistrationForm\Handlers\UpdateCompanyRegistrationFormHandler;

class UpdateCompanyRegistrationFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateCompanyRegistrationFormCommand(): UpdateCompanyRegistrationFormCommand
    {
        return new UpdateCompanyRegistrationFormCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
