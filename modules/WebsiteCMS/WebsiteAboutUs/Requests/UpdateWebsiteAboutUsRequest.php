<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\WebsiteCMS\WebsiteAboutUs\DTO\UpdateWebsiteAboutUsDTO;

class UpdateWebsiteAboutUsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'description_ar' => 'required|string',
            'description_en' => 'required|string',
            'is_certificates' => 'required|boolean',
            'is_approvals' => 'required|boolean',
            'is_companies' => 'required|boolean',
            'about_me_ar' => 'required|string',
            'about_me_en' => 'required|string',
            'vision_ar' => 'required|string',
            'vision_en' => 'required|string',
            'target_ar' => 'required|string',
            'target_en' => 'required|string',
            'slogan_ar' => 'required|string|max:500',
            'slogan_en' => 'required|string|max:500',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',

            // Project types array validation
            'project_types' => 'nullable|array',
            'project_types.*.title_ar' => 'required|string|max:255',
            'project_types.*.title_en' => 'required|string|max:255',
            'project_types.*.count' => 'required|integer|min:0',

            // Attachments array validation
            'attachments' => 'nullable|array',
            'attachments.*.name' => 'required|string|max:255',
            'attachments.*.attachment' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar|max:10240',
        ];
    }

    public function createUpdateWebsiteAboutUsDTO(): UpdateWebsiteAboutUsDTO
    {
        return new UpdateWebsiteAboutUsDTO(
            id: $this->route('id'),
            title: [
                'ar' => $this->get('title_ar'),
                'en' => $this->get('title_en'),
            ],
            description: [
                'ar' => $this->get('description_ar'),
                'en' => $this->get('description_en'),
            ],
            is_certificates: $this->boolean('is_certificates'),
            is_approvals: $this->boolean('is_approvals'),
            is_companies: $this->boolean('is_companies'),
            about_me: [
                'ar' => $this->get('about_me_ar'),
                'en' => $this->get('about_me_en'),
            ],
            vision: [
                'ar' => $this->get('vision_ar'),
                'en' => $this->get('vision_en'),
            ],
            target: [
                'ar' => $this->get('target_ar'),
                'en' => $this->get('target_en'),
            ],
            slogan: [
                'ar' => $this->get('slogan_ar'),
                'en' => $this->get('slogan_en'),
            ],
            main_image: $this->file('main_image'),
            project_types: $this->get('project_types'),
            attachments: $this->get('attachments'),
        );
    }
}
