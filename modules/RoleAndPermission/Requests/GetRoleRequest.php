<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
