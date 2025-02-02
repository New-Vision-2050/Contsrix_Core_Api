<?php

declare(strict_types=1);

namespace Modules\Company\CompanyField\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyField\Commands\UpdateCompanyFieldCommand;
use Modules\Company\CompanyField\Handlers\UpdateCompanyFieldHandler;

class UpdateCompanyFieldRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateCompanyFieldCommand(): UpdateCompanyFieldCommand
    {
        return new UpdateCompanyFieldCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            description: $this->get('description'),
        );
    }
}
