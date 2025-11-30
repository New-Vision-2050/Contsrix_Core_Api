<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetWebsiteThemeSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
