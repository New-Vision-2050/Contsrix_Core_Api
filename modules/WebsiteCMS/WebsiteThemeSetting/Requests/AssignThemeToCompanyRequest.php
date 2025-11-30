<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteThemeSetting\DTO\AssignThemeToCompanyDTO;

class AssignThemeToCompanyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'website_theme_setting_id' => 'required|uuid|exists:website_theme_settings,id',
        ];
    }

    public function toDTO(): AssignThemeToCompanyDTO
    {
        return new AssignThemeToCompanyDTO(
            company_id: Uuid::fromString(tenant("id")),
            website_theme_setting_id: Uuid::fromString($this->get('website_theme_setting_id')),
        );
    }
}
