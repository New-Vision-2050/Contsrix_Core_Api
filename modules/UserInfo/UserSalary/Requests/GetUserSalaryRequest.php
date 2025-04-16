<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserSalary\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetUserSalaryRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
