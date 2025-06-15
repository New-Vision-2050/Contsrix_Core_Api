<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\BankAccount\Commands\UpdateBankAccountCommand;
use Modules\UserInfo\BankAccount\Commands\UpdateTypeBankAccountCommand;
use Modules\UserInfo\BankAccount\Handlers\UpdateBankAccountHandler;

class UpdateTypeBankAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type_id' =>'required|string',
        ];
    }

    public function createUpdateTypeBankAccountCommand(): UpdateTypeBankAccountCommand
    {
        return new UpdateTypeBankAccountCommand(
            id: Uuid::fromString($this->route('id')),
            type_id: $this->get('type_id'),
        );
    }
}
