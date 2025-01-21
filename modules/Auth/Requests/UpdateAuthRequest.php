<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Auth\Commands\UpdateAuthCommand;
use Modules\Auth\Handlers\UpdateAuthHandler;

class UpdateAuthRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateAuthCommand(): UpdateAuthCommand
    {
        return new UpdateAuthCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
