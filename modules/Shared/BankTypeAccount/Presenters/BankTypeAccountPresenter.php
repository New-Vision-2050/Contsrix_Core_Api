<?php

declare(strict_types=1);

namespace Modules\Shared\BankTypeAccount\Presenters;

use Modules\Shared\BankTypeAccount\Models\BankTypeAccount;
use BasePackage\Shared\Presenters\AbstractPresenter;

class BankTypeAccountPresenter extends AbstractPresenter
{
    private BankTypeAccount $bankTypeAccount;

    public function __construct(BankTypeAccount $bankTypeAccount)
    {
        $this->bankTypeAccount = $bankTypeAccount;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->bankTypeAccount->id,
            'name' => $this->bankTypeAccount->name,
            'code' => $this->bankTypeAccount->code,
        ];
    }
}
