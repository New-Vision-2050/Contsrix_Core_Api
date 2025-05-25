<?php

declare(strict_types=1);

namespace Modules\Shared\MaritalStatus\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteMaritalStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
