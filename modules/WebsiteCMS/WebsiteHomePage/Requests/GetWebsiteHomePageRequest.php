<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetWebsiteHomePageRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
