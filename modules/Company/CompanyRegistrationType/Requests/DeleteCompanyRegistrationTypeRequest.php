<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteCompanyRegistrationTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
