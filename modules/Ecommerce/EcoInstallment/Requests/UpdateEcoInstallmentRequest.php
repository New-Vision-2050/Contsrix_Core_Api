<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoInstallment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoInstallment\Commands\UpdateEcoInstallmentCommand;
use Modules\Ecommerce\EcoInstallment\Handlers\UpdateEcoInstallmentHandler;

class UpdateEcoInstallmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateEcoInstallmentCommand(): UpdateEcoInstallmentCommand
    {
        return new UpdateEcoInstallmentCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
