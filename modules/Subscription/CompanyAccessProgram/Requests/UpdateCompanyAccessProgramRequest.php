<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Subscription\CompanyAccessProgram\Commands\UpdateCompanyAccessProgramCommand;
use Modules\Subscription\CompanyAccessProgram\Handlers\UpdateCompanyAccessProgramHandler;

class UpdateCompanyAccessProgramRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateCompanyAccessProgramCommand(): UpdateCompanyAccessProgramCommand
    {
        return new UpdateCompanyAccessProgramCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
