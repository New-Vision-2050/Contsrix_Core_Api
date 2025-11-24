<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetSocialMediaLinkListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
        ];
    }
}
