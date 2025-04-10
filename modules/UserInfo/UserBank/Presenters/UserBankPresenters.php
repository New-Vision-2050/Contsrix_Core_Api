<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserBank\Presenters;

use Modules\UserInfo\UserBank\Models\UserBank;
use BasePackage\Shared\Presenters\AbstractPresenter;

class UserBankPresenter extends AbstractPresenter
{
    private UserBank $userBank;

    public function __construct(UserBank $userBank)
    {
        $this->userBank = $userBank;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->userBank->id,
            'name' => $this->userBank->name,
            'company_id'
            'global_id'
            'country_id'
            'bank_id'
            'currency_id'
            'user_name'
            'account_number'
            'iban'
            'swift_bic'
        ];
    }
}
