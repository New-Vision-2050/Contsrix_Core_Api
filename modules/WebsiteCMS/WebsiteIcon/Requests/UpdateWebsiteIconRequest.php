<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteIcon\Commands\UpdateWebsiteIconCommand;
use Modules\WebsiteCMS\WebsiteIcon\Handlers\UpdateWebsiteIconHandler;

class UpdateWebsiteIconRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'category_website_cms_id' => 'required|uuid|exists:category_website_cms,id',
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
            category_website_cms_id: $this->get('category_website_cms_id'),
        );
    }
}
