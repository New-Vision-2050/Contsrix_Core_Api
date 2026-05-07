<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\DTO;

use Illuminate\Http\UploadedFile;

class UpdateWebsiteHomePageSettingDTO
{
    public function __construct(
        public readonly ?string $webVideoLink = null,
        public readonly ?string $mobileVideoLink = null,
        public readonly array $description = [],
        public readonly ?int $isCompanies = null,
        public readonly ?int $isApprovals = null,
        public readonly ?int $isCertificates = null,
        public readonly ?UploadedFile $webVideoFile = null,
        public readonly ?UploadedFile $mobileVideoFile = null,
        public readonly ?UploadedFile $videoProfileFile = null,
    ) {
    }

    public function toArray(): array
    {
        $data = [];

            $data['web_video_link'] = $this->webVideoLink;


            $data['mobile_video_link'] = $this->mobileVideoLink;

        if (!empty($this->description)) {
            $data['description'] = $this->description;
        }

        if ($this->isCompanies !== null) {
            $data['is_companies'] = $this->isCompanies;
        }

        if ($this->isApprovals !== null) {
            $data['is_approvals'] = $this->isApprovals;
        }

        if ($this->isCertificates !== null) {
            $data['is_certificates'] = $this->isCertificates;
        }

        return $data;
    }
}
