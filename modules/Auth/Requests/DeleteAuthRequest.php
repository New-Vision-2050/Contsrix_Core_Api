<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteAuthRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
