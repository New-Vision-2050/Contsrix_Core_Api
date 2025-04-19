<?php

declare(strict_types=1);

namespace Modules\Shared\Privilege\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetPrivilegeRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
