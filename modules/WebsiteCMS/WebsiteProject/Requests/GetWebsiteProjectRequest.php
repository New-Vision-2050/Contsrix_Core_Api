<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetWebsiteProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
