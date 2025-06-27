<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Subscription\Module\Commands\UpdateModuleCommand;
use Modules\Subscription\Module\Handlers\UpdateModuleHandler;

class UpdateModuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateModuleCommand(): UpdateModuleCommand
    {
        return new UpdateModuleCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
