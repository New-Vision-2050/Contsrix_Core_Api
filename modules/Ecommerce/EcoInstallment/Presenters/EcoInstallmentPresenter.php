<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoInstallment\Presenters;

use Modules\Ecommerce\EcoInstallment\Models\EcoInstallment;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Installment\Presenters\InstallmentPresenter;

class EcoInstallmentPresenter extends AbstractPresenter
{
    private EcoInstallment $ecoInstallment;

    public function __construct(EcoInstallment $ecoInstallment)
    {
        $this->ecoInstallment = $ecoInstallment;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoInstallment->id,
            'company_id' => $this->ecoInstallment->company_id,
            'installment' => $this->ecoInstallment->installment ? 
                (new InstallmentPresenter($this->ecoInstallment->installment))->getData() : null,
            'is_default' => (int) $this->ecoInstallment->is_default,
            'is_active' => (int) $this->ecoInstallment->is_active,
            'created_at' => $this->ecoInstallment->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->ecoInstallment->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
