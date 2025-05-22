<?php

declare(strict_types=1);

namespace Modules\Shared\BankTypeAccount\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\BankTypeAccount\Commands\UpdateBankTypeAccountCommand;
use Modules\Shared\BankTypeAccount\Handlers\UpdateBankTypeAccountHandler;

class UpdateBankTypeAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateBankTypeAccountCommand(): UpdateBankTypeAccountCommand
    {
        return new UpdateBankTypeAccountCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
