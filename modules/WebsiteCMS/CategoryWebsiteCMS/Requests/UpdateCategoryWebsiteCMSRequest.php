<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Enum\CategoryWebsiteCMSType;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Models\CategoryWebsiteCMS;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Commands\UpdateCategoryWebsiteCMSCommand;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Handlers\UpdateCategoryWebsiteCMSHandler;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Rules\UniqueTranslationRule;

class UpdateCategoryWebsiteCMSRequest extends FormRequest
{
    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'name_ar' => [
                'required',
                'string',
                'max:255',
                new UniqueTranslationRule(new CategoryWebsiteCMS, 'name', 'ar', $categoryId)
            ],
            'name_en' => [
                'required',
                'string',
                'max:255',
                new UniqueTranslationRule(new CategoryWebsiteCMS, 'name', 'en', $categoryId)
            ],
            'category_type' => 'required|in:'.implode(",",CategoryWebsiteCMSType::values()),
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

    public function createUpdateCategoryWebsiteCMSCommand(): UpdateCategoryWebsiteCMSCommand
    {
        return new UpdateCategoryWebsiteCMSCommand(
            id: Uuid::fromString($this->route('id')),
            name: [
                'ar' => $this->get('name_ar'),
                'en' => $this->get('name_en'),
            ],
            category_type: $this->get('category_type'),
        );
    }
}
