<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetWebsiteIconRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
