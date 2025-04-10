<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserBank\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserBank\Commands\UpdateUserBankCommand;
use Modules\UserInfo\UserBank\Handlers\UpdateUserBankHandler;

class UpdateUserBankRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateUserBankCommand(): UpdateUserBankCommand
    {
        return new UpdateUserBankCommand(
            id: Uuid::fromString($this->route('id')),
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
