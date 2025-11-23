<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactInfo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteWebsiteContactInfoRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
