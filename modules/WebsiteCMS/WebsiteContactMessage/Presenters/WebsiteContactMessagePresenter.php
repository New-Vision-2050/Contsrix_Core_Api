<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Presenters;

use Modules\WebsiteCMS\WebsiteContactMessage\Models\WebsiteContactMessage;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteContactMessagePresenter extends AbstractPresenter
{
    private WebsiteContactMessage $websiteContactMessage;

    public function __construct(WebsiteContactMessage $websiteContactMessage)
    {
        $this->websiteContactMessage = $websiteContactMessage;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->websiteContactMessage->id,
            'name' => $this->websiteContactMessage->name,
        ];
    }
}
