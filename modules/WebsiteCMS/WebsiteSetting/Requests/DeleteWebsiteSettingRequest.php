<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteWebsiteSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
