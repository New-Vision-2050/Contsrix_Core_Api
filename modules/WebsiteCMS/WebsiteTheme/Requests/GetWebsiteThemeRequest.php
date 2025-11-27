<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetWebsiteThemeRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
