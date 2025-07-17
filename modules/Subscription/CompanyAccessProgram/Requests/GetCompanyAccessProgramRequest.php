<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetCompanyAccessProgramRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
