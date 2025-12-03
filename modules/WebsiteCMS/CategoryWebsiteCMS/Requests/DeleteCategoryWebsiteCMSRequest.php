<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteCategoryWebsiteCMSRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
