<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteSocialMediaRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
