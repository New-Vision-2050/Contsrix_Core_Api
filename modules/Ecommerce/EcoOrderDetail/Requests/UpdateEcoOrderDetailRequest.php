<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrderDetail\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoOrderDetail\Commands\UpdateEcoOrderDetailCommand;
use Modules\Ecommerce\EcoOrderDetail\Handlers\UpdateEcoOrderDetailHandler;

class UpdateEcoOrderDetailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateEcoOrderDetailCommand(): UpdateEcoOrderDetailCommand
    {
        return new UpdateEcoOrderDetailCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
