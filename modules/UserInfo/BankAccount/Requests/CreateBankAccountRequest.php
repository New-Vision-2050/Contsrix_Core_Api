<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\BankAccount\DTO\CreateBankAccountDTO;

class CreateBankAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|string',
            'country_id' => 'required|string',
            'bank_id' => 'required|string',
            'currency_id' => 'required|string',
            'user_name' => 'required|string',
            'iban' => 'nullable|string',
            'swift_bic' => 'nullable',
            'type_id' => 'required|string',
        ];
    }

    public function createCreateBankAccountDTO(): CreateBankAccountDTO
    {
        return new CreateBankAccountDTO(
            company_id: '',
            global_id: '',
            country_id: $this->get('country_id'),
            bank_id: $this->get('bank_id'),
            currency_id: $this->get('currency_id'),
            user_name: $this->get('user_name'),
            account_number: $this->get('account_number'),
            iban: $this->get('iban'),
            swift_bic: $this->get('swift_bic'),
            type_id: $this->get('type_id'),
        );
    }
}
