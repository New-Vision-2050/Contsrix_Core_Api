<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\WebsiteCMS\WebsiteHomePageSetting\DTO\CreateWebsiteHomePageSettingDTO;

class CreateWebsiteHomePageSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'web_video_file' => 'nullable|file|mimes:mp4,mov,avi,wmv,flv,mkv|max:102400',
            'web_video_link' => 'nullable|string|url|max:500',
            'mobile_video_file' => 'nullable|file|mimes:mp4,mov,avi,wmv,flv,mkv|max:102400',
            'mobile_video_link' => 'nullable|string|url|max:500',
            'video_profile_file' => 'nullable|file|mimes:mp4,mov,avi,wmv,flv,mkv|max:102400',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'is_companies' => 'nullable|boolean',
            'is_approvals' => 'nullable|boolean',
            'is_certificates' => 'nullable|boolean',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->hasFile('web_video_file') && !$this->filled('web_video_link')) {
                $validator->errors()->add('web_video', 'Either web_video_file or web_video_link is required');
            }

            if (!$this->hasFile('mobile_video_file') && !$this->filled('mobile_video_link')) {
                $validator->errors()->add('mobile_video', 'Either mobile_video_file or mobile_video_link is required');
            }
        });
    }

    public function createCreateWebsiteHomePageSettingDTO(): CreateWebsiteHomePageSettingDTO
    {
        return new CreateWebsiteHomePageSettingDTO(
            webVideoLink: $this->get('web_video_link'),
            mobileVideoLink: $this->get('mobile_video_link'),
            description: [
                'ar' => $this->get('description_ar'),
                'en' => $this->get('description_en'),
            ],
            isCompanies: $this->boolean('is_companies'),
            isApprovals: $this->boolean('is_approvals'),
            isCertificates: $this->boolean('is_certificates'),
            webVideoFile: $this->file('web_video_file'),
            mobileVideoFile: $this->file('mobile_video_file'),
            videoProfileFile: $this->file('video_profile_file'),
        );
    }
}
