<?php

declare(strict_types=1);

namespace Modules\Shared\University\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\University\Commands\UpdateUniversityCommand;
use Modules\Shared\University\Handlers\UpdateUniversityHandler;

class UpdateUniversityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateUniversityCommand(): UpdateUniversityCommand
    {
        return new UpdateUniversityCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
