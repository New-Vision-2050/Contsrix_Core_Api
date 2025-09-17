<?php

declare(strict_types=1);

namespace Modules\Shared\Installment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\Installment\Commands\UpdateInstallmentCommand;
use Modules\Shared\Installment\Handlers\UpdateInstallmentHandler;

class UpdateInstallmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateInstallmentCommand(): UpdateInstallmentCommand
    {
        return new UpdateInstallmentCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
