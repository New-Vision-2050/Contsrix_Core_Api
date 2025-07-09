<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Presenters\CountryCurrencyPresenter;
use Modules\Country\Presenters\CountryPresenter;
use Modules\User\Models\User;
use Modules\UserInfo\BankAccount\Presenters\BankAccountPresenter;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Modules\UserInfo\UserProfessionalData\Presenters\UserProfessionalDataPresenter;

class UserPresenter extends AbstractPresenter
{
    private User $user;
    private ?string $userId;
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->user->id,
            'user_professional_data' => $this->user->userProfessionalData ? (new UserProfessionalDataPresenter($this->user->userProfessionalData))->getData() : null,

        ];
    }
}
