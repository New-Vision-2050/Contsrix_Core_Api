<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Presenters\CountryPresenter;
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


                'file_passport' => $this->companyUser->getMedia('file_passport')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getFullUrl(),
                    ];
                }),
                'file_identity' => $this->companyUser->getMedia('file_identity')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getFullUrl(),
                    ];
                }),
                'file_border_number' => $this->companyUser->getMedia('file_border_number')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getFullUrl(),
                    ];
                }),
                'file_entry_number' => $this->companyUser->getMedia('file_entry_number')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getFullUrl(),
                    ];
                }),
            

        ];
    }
}
