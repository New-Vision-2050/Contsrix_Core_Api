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
            'company_name' => $this->bankAccount?->company?->name,
            'global_id' => $this->bankAccount->global_id,
            'country_id' => $this->bankAccount->country_id,
            'country_name' => $this->bankAccount?->country?->name,
            'bank_id' => $this->bankAccount->bank_id,
            'bank_name' => $this->bankAccount->bank?->name,
            'currency_id' => $this->bankAccount->currency_id,
            'currency_name' => $this->bankAccount->currency?->name,
            'user_name' => $this->bankAccount->user_name,
            'account_number' => $this->bankAccount->account_number,
            'iban' => $this->bankAccount->iban,
            'swift_bic' => $this->bankAccount->swift_bic,
            'type' => $this->bankAccount->type,
        ];
    }
}

