<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\Rules\CanDeleteCompanyUserRule;
use Ramsey\Uuid\Uuid;

class DeleteCompanyUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => [ new CanDeleteCompanyUserRule($this->route('id')) ],
        ];
    }
}
