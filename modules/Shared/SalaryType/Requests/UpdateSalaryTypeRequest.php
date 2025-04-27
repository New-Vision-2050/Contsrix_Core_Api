<?php

declare(strict_types=1);

namespace Modules\Shared\SalaryType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\SalaryType\Commands\UpdateSalaryTypeCommand;
use Modules\Shared\SalaryType\Handlers\UpdateSalaryTypeHandler;

class UpdateSalaryTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateSalaryTypeCommand(): UpdateSalaryTypeCommand
    {
        return new UpdateSalaryTypeCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
