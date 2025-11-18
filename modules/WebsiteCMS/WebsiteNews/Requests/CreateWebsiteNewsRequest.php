<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteNews\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteNews\DTO\CreateWebsiteNewsDTO;

class CreateWebsiteNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'content_ar' => 'required|string',
            'content_en' => 'required|string',
            'main_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'category_website_cms_id' => 'required|uuid|exists:category_website_cms,id',
            'publish_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:publish_date',
        ];
    }

    public function createCreateWebsiteNewsDTO(): CreateWebsiteNewsDTO
    {
        return new CreateWebsiteNewsDTO(
            title: [
                'ar' => $this->get('title_ar'),
                'en' => $this->get('title_en'),
            ],
            content: [
                'ar' => $this->get('content_ar'),
                'en' => $this->get('content_en'),
            ],
            main_image: $this->file('main_image'),
            thumbnail: $this->file('thumbnail'),
            category_website_cms_id: $this->get('category_website_cms_id'),
            publish_date: $this->get('publish_date'),
            end_date: $this->get('end_date'),
        );
    }
}
