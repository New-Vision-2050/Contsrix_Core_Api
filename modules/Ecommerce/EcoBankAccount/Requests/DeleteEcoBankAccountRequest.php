<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteEcoBankAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
