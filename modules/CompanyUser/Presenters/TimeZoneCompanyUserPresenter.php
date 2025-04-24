<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Presenters\AbstractPresenter;

class TimeZoneCompanyUserPresenter extends AbstractPresenter
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
            'currency' => $this->companyUser?->currency?->short_name,
            'language' => $this->companyUser?->language?->name,
            'time_zone' => $this->companyUser?->timeZone?->time_zone,
            'country' => $this->companyUser?->country?->name,
        ];

    }
}
