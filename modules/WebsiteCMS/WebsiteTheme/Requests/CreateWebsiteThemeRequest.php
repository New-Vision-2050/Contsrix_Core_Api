<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteTheme\DTO\CreateWebsiteThemeDTO;

class CreateWebsiteThemeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateWebsiteThemeDTO(): CreateWebsiteThemeDTO
    {
        return new CreateWebsiteThemeDTO(
            name: $this->get('name'),
        );
    }
}
