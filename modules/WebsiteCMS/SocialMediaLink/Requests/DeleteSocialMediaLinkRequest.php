<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteSocialMediaLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
