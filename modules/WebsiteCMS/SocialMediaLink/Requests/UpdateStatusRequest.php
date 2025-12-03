<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|integer|in:0,1',
        ];
    }
}
