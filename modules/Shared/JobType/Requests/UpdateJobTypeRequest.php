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
            'status' => 'required|in:0,1',
        ];
    }

    public function createUpdateJobTypeCommand(): UpdateJobTypeCommand
    {
        return new UpdateJobTypeCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            status: (int)$this->get("status")
        );
    }
}
