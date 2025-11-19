<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProjectSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetWebsiteProjectSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
