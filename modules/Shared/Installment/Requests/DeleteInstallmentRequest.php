<?php

declare(strict_types=1);

namespace Modules\Shared\Installment\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteInstallmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
