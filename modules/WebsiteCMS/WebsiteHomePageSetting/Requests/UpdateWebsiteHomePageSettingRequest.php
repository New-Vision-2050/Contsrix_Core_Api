<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\WebsiteCMS\WebsiteHomePageSetting\DTO\UpdateWebsiteHomePageSettingDTO;

class UpdateWebsiteHomePageSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'web_video_file' => 'nullable|file|mimes:mp4,mov,avi,wmv,flv,mkv|max:102400',
            'web_video_link' => 'nullable|string|url|max:500',
            'mobile_video_file' => 'nullable|file|mimes:mp4,mov,avi,wmv,flv,mkv|max:102400',
            'mobile_video_link' => 'nullable|string|url|max:500',
            'video_profile_file' => 'nullable|file',
            'description_ar' => 'required|string',
            'description_en' => 'required|string',
            'is_companies' => 'required|in:1,0',
            'is_approvals' => 'required|in:1,0',
            'is_certificates' => 'required|in:1,0',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // Ensure at least one web video source is provided
        if (!$this->hasFile('web_video_file') && !$this->filled('web_video_link')) {
            $this->merge([
                'web_video_validation_error' => 'Either web_video_file or web_video_link is required'
            ]);
        }

        // Ensure at least one mobile video source is provided
        if (!$this->hasFile('mobile_video_file') && !$this->filled('mobile_video_link')) {
            $this->merge([
                'mobile_video_validation_error' => 'Either mobile_video_file or mobile_video_link is required'
            ]);
        }
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

    public function toDTO(): UpdateWebsiteHomePageSettingDTO
    {
        return new UpdateWebsiteHomePageSettingDTO(
            webVideoLink: $this->get('web_video_link'),
            mobileVideoLink: $this->get('mobile_video_link'),
            description: [
                'ar' => $this->get('description_ar'),
                'en' => $this->get('description_en'),
            ],
            isCompanies: (int)$this->get('is_companies'),
            isApprovals: (int)$this->get('is_approvals'),
            isCertificates: (int)$this->get('is_certificates'),
            webVideoFile: $this->file('web_video_file'),
            mobileVideoFile: $this->file('mobile_video_file'),
            videoProfileFile: $this->file('video_profile_file'),
        );
    }
}
