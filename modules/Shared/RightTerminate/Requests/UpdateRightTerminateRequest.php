<?php

declare(strict_types=1);

namespace Modules\Shared\RightTerminate\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\RightTerminate\Commands\UpdateRightTerminateCommand;
use Modules\Shared\RightTerminate\Handlers\UpdateRightTerminateHandler;

class UpdateRightTerminateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateRightTerminateCommand(): UpdateRightTerminateCommand
    {
        return new UpdateRightTerminateCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
