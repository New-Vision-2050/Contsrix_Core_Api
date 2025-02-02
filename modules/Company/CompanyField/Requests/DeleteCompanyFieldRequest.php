<?php

declare(strict_types=1);

namespace Modules\Company\CompanyField\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteCompanyFieldRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
