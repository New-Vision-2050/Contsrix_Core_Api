<?php

declare(strict_types=1);

namespace Modules\Shared\PaymentMethodData\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\PaymentMethodData\Commands\UpdatePaymentMethodDataCommand;
use Modules\Shared\PaymentMethodData\Handlers\UpdatePaymentMethodDataHandler;

class UpdatePaymentMethodDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdatePaymentMethodDataCommand(): UpdatePaymentMethodDataCommand
    {
        return new UpdatePaymentMethodDataCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
