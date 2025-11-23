<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactInfo\Presenters;

use Modules\WebsiteCMS\WebsiteContactInfo\Models\WebsiteContactInfo;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteContactInfoPresenter extends AbstractPresenter
{
    private WebsiteContactInfo $websiteContactInfo;

    public function __construct(WebsiteContactInfo $websiteContactInfo)
    {
        $this->websiteContactInfo = $websiteContactInfo;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->websiteContactInfo->id,
            'company_id' => $this->websiteContactInfo->company_id,
            'email' => $this->websiteContactInfo->email,
            'phone' => $this->websiteContactInfo->phone,
            'created_at' => $this->websiteContactInfo->created_at?->toDateTimeString(),
            'updated_at' => $this->websiteContactInfo->updated_at?->toDateTimeString(),
        ];
    }
}
