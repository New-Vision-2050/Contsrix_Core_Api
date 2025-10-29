<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\Presenters;

use Modules\Ecommerce\SocialMedia\Models\SocialMedia;
use BasePackage\Shared\Presenters\AbstractPresenter;

class SocialMediaPresenter extends AbstractPresenter
{
    private SocialMedia $socialMedia;

    public function __construct(SocialMedia $socialMedia)
    {
        $this->socialMedia = $socialMedia;
    }

    public function getData(bool $isListing = false): array
    {
        return $this->present($isListing);
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->socialMedia->id,
            'company_id' => $this->socialMedia->company_id,
            'social_icons_id' => $this->socialMedia->social_icons_id,
            'url' => $this->socialMedia->url,
            'is_active' => $this->socialMedia->is_active,
            'social_icon' => $this->socialMedia->socialIcon ? [
                'id' => $this->socialMedia->socialIcon->id,
                'name' => $this->socialMedia->socialIcon->name,
                'icon' => $this->socialMedia->socialIcon->icon,
            ] : null,
        ];
    }
}
