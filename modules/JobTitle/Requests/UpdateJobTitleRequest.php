<?php

declare(strict_types=1);

namespace Modules\JobTitle\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\JobTitle\Commands\UpdateJobTitleCommand;
use Modules\JobTitle\Handlers\UpdateJobTitleHandler;

class UpdateJobTitleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'job_type_id' => 'required|exists:job_types,id',
            'description' => 'required|string',
        ];
    }

    public function createUpdateJobTitleCommand(): UpdateJobTitleCommand
    {
        return new UpdateJobTitleCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            job_type_id: uuid::fromString($this->get('job_type_id')),
            description: $this->get('description'),
        );
    }
}
