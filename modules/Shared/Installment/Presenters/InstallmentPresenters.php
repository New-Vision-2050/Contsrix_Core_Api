<?php

declare(strict_types=1);

namespace Modules\Shared\Installment\Presenters;

use Modules\Shared\Installment\Models\Installment;
use BasePackage\Shared\Presenters\AbstractPresenter;

class InstallmentPresenters extends AbstractPresenter
{
    private Installment $installment;

    public function __construct(Installment $installment)
    {
        $this->installment = $installment;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->installment->id,
            'name' => $this->installment->name,
        ];
    }
}
