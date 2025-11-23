<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactInfo\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteContactInfo\DTO\CreateWebsiteContactInfoDTO;

class CreateWebsiteContactInfoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateWebsiteContactInfoDTO(): CreateWebsiteContactInfoDTO
    {
        return new CreateWebsiteContactInfoDTO(
            name: $this->get('name'),
        );
    }
}
