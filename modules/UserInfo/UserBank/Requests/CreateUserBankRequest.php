<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserBank\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserBank\DTO\CreateUserBankDTO;

class CreateUserBankRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateUserBankDTO(): CreateUserBankDTO
    {
        return new CreateUserBankDTO(
            name: $this->get('name'),
            'company_id'
            'global_id'
            'country_id'
            'bank_id'
            'currency_id'
            'user_name'
            'account_number'
            'iban'
            'swift_bic'
        );
    }
}
