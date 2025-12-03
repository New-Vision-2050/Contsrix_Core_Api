<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetCurrentCompanyWebsiteOurServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
