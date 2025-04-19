<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserExperience\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetUserExperienceRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
