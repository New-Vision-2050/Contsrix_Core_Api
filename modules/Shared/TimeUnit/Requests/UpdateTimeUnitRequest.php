<?php

declare(strict_types=1);

namespace Modules\Shared\TimeUnit\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\TimeUnit\Commands\UpdateTimeUnitCommand;
use Modules\Shared\TimeUnit\Handlers\UpdateTimeUnitHandler;

class UpdateTimeUnitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateTimeUnitCommand(): UpdateTimeUnitCommand
    {
        return new UpdateTimeUnitCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
