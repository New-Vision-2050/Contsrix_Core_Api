<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\DTO;

use Illuminate\Http\UploadedFile;

class CreateWebsiteHomePageSettingDTO
{
    public function __construct(
        public readonly ?string $webVideoLink = null,
        public readonly ?string $mobileVideoLink = null,
        public readonly array $description = [],
        public readonly bool $isCompanies = false,
        public readonly bool $isApprovals = false,
        public readonly bool $isCertificates = false,
        public readonly ?UploadedFile $webVideoFile = null,
        public readonly ?UploadedFile $mobileVideoFile = null,
        public readonly ?UploadedFile $videoProfileFile = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'web_video_link' => $this->webVideoLink,
            'mobile_video_link' => $this->mobileVideoLink,
            'description' => $this->description,
            'is_companies' => $this->isCompanies,
            'is_approvals' => $this->isApprovals,
            'is_certificates' => $this->isCertificates,
        ];
    }
}
