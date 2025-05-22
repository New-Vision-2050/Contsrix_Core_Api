<?php

declare(strict_types=1);

namespace Modules\Shared\BankTypeAccount\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetBankTypeAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
