<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Presenters\CountryPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;
use Modules\User\Presenters\UserPresenter;

class CompanyIdentityDataPresenter extends AbstractPresenter
{
    private CompanyUser $companyUser;

    public function __construct(CompanyUser $companyUser)
    {
        $this->companyUser = $companyUser;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'passport' => $this->companyUser->passport,
            'passport_start_date'=> $this->companyUser->passport_start_date,
            'passport_end_date'=> $this->companyUser->passport_end_date,

            'identity' => $this->companyUser->identity,
            'identity_start_date'=> $this->companyUser->identity_start_date,
            'identity_end_date'=> $this->companyUser->identity_end_date,

            'border_number' => $this->companyUser->border_number,
            'border_number_start_date'=> $this->companyUser->border_number_start_date,
            'border_number_end_date'=> $this->companyUser->border_number_end_date,

            'entry_number' => $this->companyUser->entry_number,
            'entry_number_start_date'=> $this->companyUser->entry_number_start_date,
            'entry_number_end_date'=> $this->companyUser->entry_number_end_date,

            'work_permit' => $this->companyUser->work_permit,
            'work_permit_start_date' => $this->companyUser->work_permit_start_date,
            'work_permit_end_date' => $this->companyUser->work_permit_end_date,

            'file_passport' => MediaPresenter::collection($this->companyUser->getMedia('file_passport')),
            'file_identity' => MediaPresenter::collection($this->companyUser->getMedia('file_identity')),
            'file_border_number' => MediaPresenter::collection($this->companyUser->getMedia('file_border_number')),
            'file_entry_number' => MediaPresenter::collection($this->companyUser->getMedia('file_entry_number')),
            'file_work_permit' => MediaPresenter::collection($this->companyUser->getMedia('file_work_permit')),

        ];
    }
}
