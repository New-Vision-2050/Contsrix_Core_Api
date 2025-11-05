<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Home\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Home\Commands\UpdateHomeCommand;
use Modules\Ecommerce\Home\Handlers\UpdateHomeHandler;

class UpdateHomeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateHomeCommand(): UpdateHomeCommand
    {
        return new UpdateHomeCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
