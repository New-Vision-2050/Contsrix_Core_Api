<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Enum\CategoryWebsiteCMSType;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Models\CategoryWebsiteCMS;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\CategoryWebsiteCMS\DTO\CreateCategoryWebsiteCMSDTO;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Rules\UniqueTranslationRule;

class CreateCategoryWebsiteCMSRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_ar' => [
                'required',
                'string',
                'max:255',
                new UniqueTranslationRule(new CategoryWebsiteCMS, 'name', 'ar')
            ],
            'name_en' => [
                'required',
                'string',
                'max:255',
                new UniqueTranslationRule(new CategoryWebsiteCMS, 'name', 'en')
            ],
            'category_type' => 'required|in:'.implode(',', CategoryWebsiteCMSType::values()),
        ];
    }

    public function messages(): array
    {
        return [
            'name_ar.required' => 'Arabic name is required',
            'name_en.required' => 'English name is required',
            'category_type.required' => 'Type category is required',
            'category_type.exists' => 'Selected type category does not exist',
        ];
    }

    public function createCreateCategoryWebsiteCMSDTO(): CreateCategoryWebsiteCMSDTO
    {
        return new CreateCategoryWebsiteCMSDTO(
            name: [
                'ar' => $this->get('name_ar'),
                'en' => $this->get('name_en'),
            ],
            category_type: $this->get('category_type'),
        );
    }
}
