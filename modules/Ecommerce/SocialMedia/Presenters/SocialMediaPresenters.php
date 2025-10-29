<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\Presenters;

use Modules\Ecommerce\SocialMedia\Models\SocialMedia;
use BasePackage\Shared\Presenters\AbstractPresenter;

class SocialMediaPresenters extends AbstractPresenter
{
    private SocialMedia $socialMedia;

    public function __construct(SocialMedia $socialMedia)
    {
        $this->socialMedia = $socialMedia;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->socialMedia->id,
            'name' => $this->socialMedia->name,
        ];
    }
}
