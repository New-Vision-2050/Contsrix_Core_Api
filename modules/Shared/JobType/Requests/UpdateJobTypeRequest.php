<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\JobType\Commands\UpdateJobTypeCommand;
use Modules\Shared\JobType\Handlers\UpdateJobTypeHandler;

class UpdateJobTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'status' => 'sometimes|boolean',
        ];
    }

    public function createUpdateJobTypeCommand(): UpdateJobTypeCommand
    {
        return new UpdateJobTypeCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            status: $this->has('status') ? (bool)$this->get('status') : null
        );
    }
}
