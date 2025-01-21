<?php

declare(strict_types=1);

namespace Modules\Company\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\Commands\UpdateCompanyCommand;
use Modules\Company\Handlers\UpdateCompanyHandler;

class UpdateCompanyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:companies,email,' . $this->route('id'),
            'phone' => 'required|unique:companies,phone,' . $this->route('id'),
        ];
    }

    public function createUpdateCompanyCommand(): UpdateCompanyCommand
    {
        return new UpdateCompanyCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            email:$this->get('email'),
            phone:$this->get('phone'),
        );
    }
}
