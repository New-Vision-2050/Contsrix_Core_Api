<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\TimeZone\Commands\UpdateTimeZoneCommand;
use Modules\Shared\TimeZone\Handlers\UpdateTimeZoneHandler;

class UpdateTimeZoneRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateTimeZoneCommand(): UpdateTimeZoneCommand
    {
        return new UpdateTimeZoneCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
