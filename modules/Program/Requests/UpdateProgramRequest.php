<?php

declare(strict_types=1);

namespace Modules\Program\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Program\Commands\UpdateProgramCommand;
use Modules\Program\Handlers\UpdateProgramHandler;

class UpdateProgramRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateProgramCommand(): UpdateProgramCommand
    {
        return new UpdateProgramCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
