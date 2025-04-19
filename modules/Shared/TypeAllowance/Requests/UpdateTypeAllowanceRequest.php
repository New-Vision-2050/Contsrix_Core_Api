<?php

declare(strict_types=1);

namespace Modules\Shared\TypeAllowance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\TypeAllowance\Commands\UpdateTypeAllowanceCommand;
use Modules\Shared\TypeAllowance\Handlers\UpdateTypeAllowanceHandler;

class UpdateTypeAllowanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateTypeAllowanceCommand(): UpdateTypeAllowanceCommand
    {
        return new UpdateTypeAllowanceCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
