<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetEmploymentContractRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
