<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetUserRelativeRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
