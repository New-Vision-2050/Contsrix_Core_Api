<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Subscription\Package\Commands\UpdatePackageCommand;
use Modules\Subscription\Package\Handlers\UpdatePackageHandler;

class UpdatePackageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdatePackageCommand(): UpdatePackageCommand
    {
        return new UpdatePackageCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
