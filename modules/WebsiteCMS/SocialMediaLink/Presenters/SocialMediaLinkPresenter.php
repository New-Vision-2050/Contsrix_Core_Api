<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Presenters;

use Modules\WebsiteCMS\SocialMediaLink\Enums\SocialMediaType;
use Modules\WebsiteCMS\SocialMediaLink\Models\SocialMediaLink;
use BasePackage\Shared\Presenters\AbstractPresenter;

class SocialMediaLinkPresenter extends AbstractPresenter
{
    private SocialMediaLink $socialMediaLink;

    public function __construct(SocialMediaLink $socialMediaLink)
    {
        $this->socialMediaLink = $socialMediaLink;
    }

    protected function present(bool $isListing = false): array
    {
        $icon = $this->socialMediaLink->getFirstMedia('icon');

        return [
            'id' => $this->socialMediaLink->id,
            'type' => [
                "id"=>$this->socialMediaLink->type ,
                "name"=>SocialMediaType::label($this->socialMediaLink->type)
            ],
            'link' => $this->socialMediaLink->link,
            'status' => $this->socialMediaLink->status,
            'icon_url' => $icon ? $icon->getUrl() : null,
            'icon_name' => $icon ? $icon->file_name : null,
            'company_id' => $this->socialMediaLink->company_id,
            'created_at' => $this->socialMediaLink->created_at?->toDateTimeString(),
            'updated_at' => $this->socialMediaLink->updated_at?->toDateTimeString(),
        ];
    }
}
