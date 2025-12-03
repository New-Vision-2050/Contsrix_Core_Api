<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetWebsiteAboutUsListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
        ];
    }
}
