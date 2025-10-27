<?php

declare(strict_types=1);

namespace Modules\Unit\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Unit\Commands\UpdateUnitCommand;
use Modules\Unit\Handlers\UpdateUnitHandler;

class UpdateUnitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateUnitCommand(): UpdateUnitCommand
    {
        return new UpdateUnitCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
