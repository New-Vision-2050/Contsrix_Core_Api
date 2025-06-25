<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Modules\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\SubscriptionSystem\Modules\Commands\UpdateModulesCommand;
use Modules\SubscriptionSystem\Modules\Handlers\UpdateModulesHandler;

class UpdateModulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateModulesCommand(): UpdateModulesCommand
    {
        return new UpdateModulesCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
