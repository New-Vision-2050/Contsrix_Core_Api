<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\WebsiteCMS\WebsiteHomePage\DTO\GetHomePageDataDTO;

class GetHomePageDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function toDTO(): GetHomePageDataDTO
    {
        return new GetHomePageDataDTO(
            limit: $this->integer('limit', 3)
        );
    }
}
