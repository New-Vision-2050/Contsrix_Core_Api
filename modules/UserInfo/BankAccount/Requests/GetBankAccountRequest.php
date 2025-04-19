<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetBankAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
