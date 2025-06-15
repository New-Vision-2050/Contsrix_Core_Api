<?php

declare(strict_types=1);

namespace Modules\Shared\TypeWorkingHour\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\TypeWorkingHour\Commands\UpdateTypeWorkingHourCommand;
use Modules\Shared\TypeWorkingHour\Handlers\UpdateTypeWorkingHourHandler;

class UpdateTypeWorkingHourRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateTypeWorkingHourCommand(): UpdateTypeWorkingHourCommand
    {
        return new UpdateTypeWorkingHourCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
