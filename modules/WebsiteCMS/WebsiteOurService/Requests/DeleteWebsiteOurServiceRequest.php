<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteWebsiteOurServiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
