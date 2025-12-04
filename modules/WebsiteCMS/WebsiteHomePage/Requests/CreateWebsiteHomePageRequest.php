<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteHomePage\DTO\CreateWebsiteHomePageDTO;

class CreateWebsiteHomePageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateWebsiteHomePageDTO(): CreateWebsiteHomePageDTO
    {
        return new CreateWebsiteHomePageDTO(
            name: $this->get('name'),
        );
    }
}
