<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetManagementHierarchyRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
