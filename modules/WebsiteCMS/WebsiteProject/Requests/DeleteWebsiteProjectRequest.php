<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteWebsiteProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
