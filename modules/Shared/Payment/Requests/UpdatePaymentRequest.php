<?php

declare(strict_types=1);

namespace Modules\Shared\Payment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\Payment\Commands\UpdatePaymentCommand;
use Modules\Shared\Payment\Handlers\UpdatePaymentHandler;

class UpdatePaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdatePaymentCommand(): UpdatePaymentCommand
    {
        return new UpdatePaymentCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
