<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Presenters;

use Modules\Ecommerce\EcoBankAccount\Models\EcoBankAccount;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Presenters\CountryPresenter;
use Modules\Shared\Bank\Presenters\BankPresenter;

class EcoBankAccountPresenter extends AbstractPresenter
{
    private EcoBankAccount $ecoBankAccount;

    public function __construct(EcoBankAccount $ecoBankAccount)
    {
        $this->ecoBankAccount = $ecoBankAccount;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoBankAccount->id,
            'bank_id' => $this->ecoBankAccount->bank? (new BankPresenter($this->ecoBankAccount->bank))->getData() : null,
            'account_holder_name' => $this->ecoBankAccount->account_holder_name,
            'account_number' => $this->ecoBankAccount->account_number,
            'iban' => $this->ecoBankAccount->iban,
            'country' => $this->ecoBankAccount->country? (new CountryPresenter($this->ecoBankAccount->country))->getData() : null,
            'is_primary' => (int) $this->ecoBankAccount->is_primary,
            'is_active' => (int) $this->ecoBankAccount->is_active,
        ];
    }
}
