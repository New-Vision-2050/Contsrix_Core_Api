<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\AdminRequest\Commands\UpdateAdminRequestCommand;
use Modules\AdminRequest\Handlers\UpdateAdminRequestHandler;

class UpdateAdminRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateAdminRequestCommand(): UpdateAdminRequestCommand
    {
        return new UpdateAdminRequestCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
