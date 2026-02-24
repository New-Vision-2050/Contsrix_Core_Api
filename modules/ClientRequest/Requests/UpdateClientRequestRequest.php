<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\ClientRequest\Commands\UpdateClientRequestCommand;
use Modules\ClientRequest\Handlers\UpdateClientRequestHandler;

class UpdateClientRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateClientRequestCommand(): UpdateClientRequestCommand
    {
        return new UpdateClientRequestCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
