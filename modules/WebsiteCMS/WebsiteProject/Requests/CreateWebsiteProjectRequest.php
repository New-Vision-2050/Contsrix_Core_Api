<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\WebsiteCMS\WebsiteProject\DTO\CreateWebsiteProjectDTO;

class CreateWebsiteProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'website_project_setting_id' => 'required|uuid|exists:website_project_settings,id',
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'secondary_images' => 'nullable|array',
            'secondary_images.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'project_details' => 'nullable|array',
            'project_details.*.name_ar' => 'required|string|max:255',
            'project_details.*.name_en' => 'required|string|max:255',
            'project_details.*.website_service_id' => 'required|uuid|exists:website_services,id',
        ];
    }

    public function createCreateWebsiteProjectDTO(): CreateWebsiteProjectDTO
    {
        return new CreateWebsiteProjectDTO(
            websiteProjectSettingId: $this->get('website_project_setting_id'),
            title: [
                'ar' => $this->get('title_ar'),
                'en' => $this->get('title_en'),
            ],
            name: [
                'ar' => $this->get('name_ar'),
                'en' => $this->get('name_en'),
            ],
            description: [
                'ar' => $this->get('description_ar'),
                'en' => $this->get('description_en'),
            ],
            mainImage: $this->file('main_image'),
            secondaryImages: $this->file('secondary_images') ?? [],
            projectDetails: $this->get('project_details', []),
        );
    }
}
