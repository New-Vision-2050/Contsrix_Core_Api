<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoOrder\Commands\UpdateEcoOrderCommand;
use Modules\Ecommerce\EcoOrder\Handlers\UpdateEcoOrderHandler;

class UpdateEcoOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateEcoOrderCommand(): UpdateEcoOrderCommand
    {
        return new UpdateEcoOrderCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
