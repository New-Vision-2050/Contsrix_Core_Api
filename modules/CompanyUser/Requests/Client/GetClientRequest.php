<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetClientRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
