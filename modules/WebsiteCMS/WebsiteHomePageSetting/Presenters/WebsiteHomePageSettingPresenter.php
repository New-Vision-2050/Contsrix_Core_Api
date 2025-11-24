<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\Presenters;

use Modules\WebsiteCMS\WebsiteHomePageSetting\Models\WebsiteHomePageSetting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteHomePageSettingPresenter extends AbstractPresenter
{
    private WebsiteHomePageSetting $websiteHomePageSetting;

    public function __construct(WebsiteHomePageSetting $websiteHomePageSetting)
    {
        $this->websiteHomePageSetting = $websiteHomePageSetting;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->websiteHomePageSetting->id,
            'company_id' => $this->websiteHomePageSetting->company_id,
            'web_video_link' => $this->websiteHomePageSetting->web_video_link,
            'mobile_video_link' => $this->websiteHomePageSetting->mobile_video_link,
            'description' => $this->websiteHomePageSetting->description,
            'is_companies' => $this->websiteHomePageSetting->is_companies,
            'is_approvals' => $this->websiteHomePageSetting->is_approvals,
            'is_certificates' => $this->websiteHomePageSetting->is_certificates,
            'web_video_file' => $this->websiteHomePageSetting->getFirstMediaUrl('web_video_file') ?: null,
            'mobile_video_file' => $this->websiteHomePageSetting->getFirstMediaUrl('mobile_video_file') ?: null,
            'video_profile_file' => $this->websiteHomePageSetting->getFirstMediaUrl('video_profile_file') ?: null,
            'status' => $this->websiteHomePageSetting->status,
            'created_at' => $this->websiteHomePageSetting->created_at,
            'updated_at' => $this->websiteHomePageSetting->updated_at,
        ];
    }
}
