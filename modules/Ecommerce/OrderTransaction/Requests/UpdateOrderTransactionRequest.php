<?php

declare(strict_types=1);

namespace Modules\Ecommerce\OrderTransaction\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\OrderTransaction\Commands\UpdateOrderTransactionCommand;
use Modules\Ecommerce\OrderTransaction\Handlers\UpdateOrderTransactionHandler;

class UpdateOrderTransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateOrderTransactionCommand(): UpdateOrderTransactionCommand
    {
        return new UpdateOrderTransactionCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
