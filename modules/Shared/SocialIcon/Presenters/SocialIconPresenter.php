<?php

declare(strict_types=1);

namespace Modules\Shared\SocialIcon\Presenters;

use Modules\Shared\SocialIcon\Models\SocialIcon;
use BasePackage\Shared\Presenters\AbstractPresenter;

class SocialIconPresenter extends AbstractPresenter
{
    private SocialIcon $socialIcon;

    public function __construct(SocialIcon $socialIcon)
    {
        $this->socialIcon = $socialIcon;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->socialIcon->id,
            'name' => $this->socialIcon->name,
            'web_icon' => $this->socialIcon->web_icon,
            'mobile_icon' => $this->socialIcon->mobile_icon,
        ];
    }
}
