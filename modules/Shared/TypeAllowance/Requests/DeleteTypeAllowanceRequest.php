<?php

declare(strict_types=1);

namespace Modules\Shared\TypeAllowance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteTypeAllowanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
