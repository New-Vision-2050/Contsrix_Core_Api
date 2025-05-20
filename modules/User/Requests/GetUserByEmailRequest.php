<?php

declare(strict_types=1);

namespace Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetUserByEmailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "email" => "required|email"
        ];
    }
}
