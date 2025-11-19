<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetWebsiteTermAndConditionRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
