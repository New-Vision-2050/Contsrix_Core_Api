<?php

declare(strict_types=1);

namespace Modules\UserInfo\Social\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetSocialRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
