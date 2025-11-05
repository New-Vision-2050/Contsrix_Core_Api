<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\PaymentMethod\Commands\UpdatePaymentMethodCommand;
use Modules\Ecommerce\PaymentMethod\Handlers\UpdatePaymentMethodHandler;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdatePaymentMethodCommand(): UpdatePaymentMethodCommand
    {
        return new UpdatePaymentMethodCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
