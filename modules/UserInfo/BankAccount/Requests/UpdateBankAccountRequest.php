<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\BankAccount\Commands\UpdateBankAccountCommand;
use Modules\UserInfo\BankAccount\Handlers\UpdateBankAccountHandler;

class UpdateBankAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'country_id' => 'required|string',
            'bank_id' => 'required|string',
            'currency_id' => 'required|string',
            'user_name' => 'required|string',
            'iban' => 'required|string',
            'swift_bic' => 'nullable',
            'type_id' =>'required|string',
        ];
    }

    public function createUpdateBankAccountCommand(): UpdateBankAccountCommand
    {
        return new UpdateBankAccountCommand(
            id: Uuid::fromString($this->route('id')),
            country_id: $this->get('country_id'),
            bank_id: $this->get('bank_id'),
            currency_id: $this->get('currency_id'),
            user_name: $this->get('user_name'),
            account_number: $this->get('account_number'),
            iban: $this->get('iban'),
            swift_bic: $this->get('swift_bic') ?? '',
            type_id: $this->get('type_id'),
        );
    }
}
