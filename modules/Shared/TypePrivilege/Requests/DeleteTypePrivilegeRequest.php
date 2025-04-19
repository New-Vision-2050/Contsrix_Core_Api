<?php

declare(strict_types=1);

namespace Modules\Shared\TypePrivilege\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteTypePrivilegeRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
