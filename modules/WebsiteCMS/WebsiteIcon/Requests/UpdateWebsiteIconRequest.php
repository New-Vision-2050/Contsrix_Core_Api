<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Requests;

use App\Rules\UniqueTranslationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\WebsiteCMS\WebsiteIcon\Models\WebsiteIcon;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteIcon\Commands\UpdateWebsiteIconCommand;
use Modules\WebsiteCMS\WebsiteIcon\Handlers\UpdateWebsiteIconHandler;
use Modules\WebsiteCMS\WebsiteIcon\Enums\WebsiteIconCategoryType;

class UpdateWebsiteIconRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_ar' => ['required','string','max:255',new UniqueTranslationRule(new WebsiteIcon(),'name','ar',$this->route('id'))],
            'name_en' => ['required','string','max:255',new UniqueTranslationRule(new WebsiteIcon(),'name','en',$this->route('id'))],
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'website_icon_category_type' => ['required', 'string', Rule::in(WebsiteIconCategoryType::values())],
        ];
    }

    public function createUpdateWebsiteIconCommand(): UpdateWebsiteIconCommand
    {
        return new UpdateWebsiteIconCommand(
            id: Uuid::fromString($this->route('id')),
            name: [
                'ar' => $this->get('name_ar'),
                'en' => $this->get('name_en'),
            ],
            icon: $this->file('icon'),
            website_icon_category_type: WebsiteIconCategoryType::from($this->get('website_icon_category_type')),
        );
    }
}
