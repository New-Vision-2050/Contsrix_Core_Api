<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\ActivityLog\Commands\UpdateActivityLogCommand;
use Modules\ActivityLog\Handlers\UpdateActivityLogHandler;

class UpdateActivityLogRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateActivityLogCommand(): UpdateActivityLogCommand
    {
        return new UpdateActivityLogCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
