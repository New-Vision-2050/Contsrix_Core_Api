<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetContactinfoRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
