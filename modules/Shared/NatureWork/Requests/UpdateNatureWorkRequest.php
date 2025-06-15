<?php

declare(strict_types=1);

namespace Modules\Shared\NatureWork\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\NatureWork\Commands\UpdateNatureWorkCommand;
use Modules\Shared\NatureWork\Handlers\UpdateNatureWorkHandler;

class UpdateNatureWorkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateNatureWorkCommand(): UpdateNatureWorkCommand
    {
        return new UpdateNatureWorkCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
