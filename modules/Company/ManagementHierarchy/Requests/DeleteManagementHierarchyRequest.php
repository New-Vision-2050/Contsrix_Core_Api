<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteManagementHierarchyRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
