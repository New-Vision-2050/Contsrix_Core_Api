<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoCurrency\Commands\UpdateEcoCurrencyCommand;
use Modules\Ecommerce\EcoCurrency\Handlers\UpdateEcoCurrencyHandler;

class UpdateEcoCurrencyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateEcoCurrencyCommand(): UpdateEcoCurrencyCommand
    {
        return new UpdateEcoCurrencyCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
