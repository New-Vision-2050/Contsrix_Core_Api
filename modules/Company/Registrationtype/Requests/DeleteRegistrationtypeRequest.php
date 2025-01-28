<?php

declare(strict_types=1);

namespace Modules\Company\RegistrationType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteRegistrationTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
