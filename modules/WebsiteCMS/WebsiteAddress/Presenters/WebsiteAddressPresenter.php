<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Presenters;

use Modules\WebsiteCMS\WebsiteAddress\Models\WebsiteAddress;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteAddressPresenter extends AbstractPresenter
{
    private WebsiteAddress $websiteAddress;

    public function __construct(WebsiteAddress $websiteAddress)
    {
        $this->websiteAddress = $websiteAddress;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->websiteAddress->id,
            'city_id' => $this->websiteAddress->city_id,
            'city' => $this->websiteAddress->city ? [
                'id' => $this->websiteAddress->city->id,
                'name' => $this->websiteAddress->city->name,
            ] : null,
            'title' => $this->websiteAddress->title,
            'title_ar' => $this->websiteAddress->getTranslation("title","ar"),
            'title_en' => $this->websiteAddress->getTranslation("title","ar"),
            'latitude' => $this->websiteAddress->latitude,
            'longitude' => $this->websiteAddress->longitude,
            'status' => $this->websiteAddress->status,
            'created_at' => $this->websiteAddress->created_at?->toDateTimeString(),
            'updated_at' => $this->websiteAddress->updated_at?->toDateTimeString(),
        ];
    }
}
