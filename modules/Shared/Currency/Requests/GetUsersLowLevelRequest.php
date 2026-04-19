<?php

declare(strict_types=1);

namespace Modules\Shared\Currency\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetUsersLowLevelRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'name' => 'nullable|string|max:255',
        ];
    }
}
