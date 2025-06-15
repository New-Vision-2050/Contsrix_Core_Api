<?php

declare(strict_types=1);

namespace Modules\UserInfo\Social\Presenters;

use Modules\UserInfo\Social\Models\Social;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\CompanyUser\Models\CompanyUser;

class SocialPresenter extends AbstractPresenter
{
    private CompanyUser $companyUser;

    public function __construct(CompanyUser $companyUser)
    {
        $this->companyUser = $companyUser;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->companyUser->id,
            "whatsapp"=> $this->companyUser->whatsapp,
            "facebook"=> $this->companyUser->facebook,
            "telegram"=> $this->companyUser->telegram,
            "instagram"=> $this->companyUser->instagram,
            "snapchat"=> $this->companyUser->snapchat,
            "linkedin"=> $this->companyUser->linkedin,
        ];
    }
}
