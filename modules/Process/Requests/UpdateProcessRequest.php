<?php

declare(strict_types=1);

namespace Modules\Process\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Process\Commands\UpdateProcessCommand;
use Modules\Process\Handlers\UpdateProcessHandler;

class UpdateProcessRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateProcessCommand(): UpdateProcessCommand
    {
        return new UpdateProcessCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
