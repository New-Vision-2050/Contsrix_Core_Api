<?php

declare(strict_types=1);

namespace Modules\Shared\MaritalStatus\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\MaritalStatus\Commands\UpdateMaritalStatusCommand;
use Modules\Shared\MaritalStatus\Handlers\UpdateMaritalStatusHandler;

class UpdateMaritalStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateMaritalStatusCommand(): UpdateMaritalStatusCommand
    {
        return new UpdateMaritalStatusCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
