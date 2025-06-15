<?php

declare(strict_types=1);

namespace Modules\Shared\SalaryType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteSalaryTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
