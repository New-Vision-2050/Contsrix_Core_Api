<?php

declare(strict_types=1);

namespace Modules\Shared\Bank\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteBankRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
