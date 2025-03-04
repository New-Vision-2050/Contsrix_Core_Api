<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetCompanyUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
