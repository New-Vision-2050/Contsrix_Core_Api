<?php

declare(strict_types=1);

namespace Modules\Shared\Bank\Presenters;

use Modules\Shared\Bank\Models\Bank;
use BasePackage\Shared\Presenters\AbstractPresenter;

class BankPresenter extends AbstractPresenter
{
    private Bank $bank;

    public function __construct(Bank $bank)
    {
        $this->bank = $bank;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->bank->id,
            'name' => $this->bank->name,
        ];
    }
}
