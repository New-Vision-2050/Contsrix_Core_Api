<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Requests;

use Ramsey\Uuid\Uuid;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Subscription\CompanyAccessProgram\Commands\UpdateCompanyAccessProgramStatusCommand;

class UpdateCompanyAccessProgramStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|boolean',
        ];
    }

    public function createUpdateCompanyAccessProgramStatusCommand(): UpdateCompanyAccessProgramStatusCommand
    {
        return new UpdateCompanyAccessProgramStatusCommand(
            id: Uuid::fromString($this->route('id')),
            status: $this->get('status'),
        );
    }
}
