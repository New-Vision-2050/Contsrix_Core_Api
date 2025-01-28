<?php

declare(strict_types=1);

namespace Modules\Company\CompanyType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyType\Commands\UpdateCompanyTypeCommand;
use Modules\Company\CompanyType\Handlers\UpdateCompanyTypeHandler;

class UpdateCompanyTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateCompanyTypeCommand(): UpdateCompanyTypeCommand
    {
        return new UpdateCompanyTypeCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
