<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Order\Commands\UpdateOrderCommand;
use Modules\Ecommerce\Order\Handlers\UpdateOrderHandler;

class UpdateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateOrderCommand(): UpdateOrderCommand
    {
        return new UpdateOrderCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
