<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoPayment\Commands\UpdateEcoPaymentCommand;
use Modules\Ecommerce\EcoPayment\Handlers\UpdateEcoPaymentHandler;

class UpdateEcoPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateEcoPaymentCommand(): UpdateEcoPaymentCommand
    {
        return new UpdateEcoPaymentCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
