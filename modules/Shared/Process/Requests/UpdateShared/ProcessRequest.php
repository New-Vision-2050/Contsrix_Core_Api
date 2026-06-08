<?php

declare(strict_types=1);

namespace Modules\Shared/Process\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared/Process\Commands\UpdateShared/ProcessCommand;
use Modules\Shared/Process\Handlers\UpdateShared/ProcessHandler;

class UpdateShared/ProcessRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateShared/ProcessCommand(): UpdateShared/ProcessCommand
    {
        return new UpdateShared/ProcessCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
