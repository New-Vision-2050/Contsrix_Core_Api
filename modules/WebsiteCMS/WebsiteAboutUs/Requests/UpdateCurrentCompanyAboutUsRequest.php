<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\WebsiteCMS\WebsiteAboutUs\DTO\UpdateWebsiteAboutUsDTO;

class UpdateCurrentCompanyAboutUsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'is_certificates' => 'required|in:1,0',
            'is_approvals' => 'required|in:1,0',
            'is_companies' => 'required|in:1,0',
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
            'attachments.*.attachment' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar|max:10240',
        ];
    }

    public function createUpdateWebsiteAboutUsDTO(): UpdateWebsiteAboutUsDTO
    {
        return new UpdateWebsiteAboutUsDTO(
            id: '', // Not needed for current company update
            title: $this->get("title"),
            description:$this->get("description"),
            is_certificates: (int)$this->get('is_certificates'),
            is_approvals: (int)$this->get('is_approvals'),
            is_companies: (int)$this->get('is_companies'),
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
            attachments: $this->attachments,
        );
    }
}
