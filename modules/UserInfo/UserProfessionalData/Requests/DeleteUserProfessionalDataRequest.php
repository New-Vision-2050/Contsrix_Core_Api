<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteUserProfessionalDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
