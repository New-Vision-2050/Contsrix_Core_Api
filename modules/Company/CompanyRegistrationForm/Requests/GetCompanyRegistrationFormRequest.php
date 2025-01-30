<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationForm\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetCompanyRegistrationFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
