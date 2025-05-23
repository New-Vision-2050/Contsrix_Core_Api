<?php

declare(strict_types=1);

namespace Modules\Shared\BankTypeAccount\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\BankTypeAccount\DTO\CreateBankTypeAccountDTO;

class CreateBankTypeAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateBankTypeAccountDTO(): CreateBankTypeAccountDTO
    {
        return new CreateBankTypeAccountDTO(
            name: $this->get('name'),
        );
    }
}
