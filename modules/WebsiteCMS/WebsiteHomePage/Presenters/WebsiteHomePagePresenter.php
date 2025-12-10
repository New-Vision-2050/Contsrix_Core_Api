<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Presenters;

use Modules\WebsiteCMS\WebsiteHomePage\Models\WebsiteHomePage;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteHomePagePresenter extends AbstractPresenter
{
    private WebsiteHomePage $websiteHomePage;

    public function __construct(WebsiteHomePage $websiteHomePage)
    {
        $this->websiteHomePage = $websiteHomePage;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->websiteHomePage->id,
            'name' => $this->websiteHomePage->name,
        ];
    }
}
