<?php

declare(strict_types=1);

namespace Modules\UserInfo\BankAccount\Presenters;

use Modules\UserInfo\BankAccount\Models\BankAccount;
use BasePackage\Shared\Presenters\AbstractPresenter;

class BankAccountPresenter extends AbstractPresenter
{
    private BankAccount $bankAccount;

    public function __construct(BankAccount $bankAccount)
    {
        $this->bankAccount = $bankAccount;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->bankAccount->id,
            'company_id' => $this->bankAccount->company_id,
            'global_id' => $this->bankAccount->global_id,
            'country_id' => $this->bankAccount->country_id,
            'bank_id' => $this->bankAccount->bank_id,
            'currency_id' => $this->bankAccount->currency_id,
            'user_name' => $this->bankAccount->user_name,
            'account_number' => $this->bankAccount->account_number,
            'iban' => $this->bankAccount->iban,
            'swift_bic' => $this->bankAccount->swift_bic,
        ];
    }
}
