<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteNews\Presenters;

use Modules\WebsiteCMS\WebsiteNews\Models\WebsiteNews;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteNewsPresenters extends AbstractPresenter
{
    private WebsiteNews $websiteNews;

    public function __construct(WebsiteNews $websiteNews)
    {
        $this->websiteNews = $websiteNews;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->websiteNews->id,
            'name' => $this->websiteNews->name,
        ];
    }
}
