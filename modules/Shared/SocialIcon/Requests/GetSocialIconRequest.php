<?php

declare(strict_types=1);

namespace Modules\Shared\SocialIcon\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetSocialIconRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
