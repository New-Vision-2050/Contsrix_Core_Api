<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteNews\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
