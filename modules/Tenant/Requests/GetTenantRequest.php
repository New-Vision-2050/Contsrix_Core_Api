<?php

declare(strict_types=1);

namespace Modules\Tenant\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetTenantRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
