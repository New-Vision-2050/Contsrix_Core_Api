<?php

declare(strict_types=1);

namespace Modules\Company\CompanyType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetCompanyTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
