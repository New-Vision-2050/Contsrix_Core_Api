<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProjectSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteProjectSetting\DTO\CreateWebsiteProjectSettingDTO;

class CreateWebsiteProjectSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
        ];
    }

    public function createCreateWebsiteProjectSettingDTO(): CreateWebsiteProjectSettingDTO
    {
        return new CreateWebsiteProjectSettingDTO(
            name: [
                'ar' => $this->get('name_ar'),
                'en' => $this->get('name_en'),
            ],
        );
    }
}
