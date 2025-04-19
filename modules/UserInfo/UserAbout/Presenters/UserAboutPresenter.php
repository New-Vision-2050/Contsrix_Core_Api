<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserAbout\Presenters;

use Modules\UserInfo\UserAbout\Models\UserAbout;
use BasePackage\Shared\Presenters\AbstractPresenter;

class UserAboutPresenter extends AbstractPresenter
{
    private UserAbout $userAbout;

    public function __construct(UserAbout $userAbout)
    {
        $this->userAbout = $userAbout;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->userAbout->id,
            'about_me' => $this->userAbout->about_me,
        ];
    }
}
