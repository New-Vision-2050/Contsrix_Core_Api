<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Presenters;

use Modules\Ecommerce\EcoBusinessActivity\Models\EcoBusinessActivity;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Company\CompanyField\Presenters\CompanyFieldPresenter;

class EcoBusinessActivityPresenter extends AbstractPresenter
{
    private EcoBusinessActivity $ecoBusinessActivity;

    public function __construct(EcoBusinessActivity $ecoBusinessActivity)
    {
        $this->ecoBusinessActivity = $ecoBusinessActivity;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoBusinessActivity->id,
            'company_field' => $this->ecoBusinessActivity->companyField ? (new CompanyFieldPresenter($this->ecoBusinessActivity->companyField))->getData() : null,
            'business_name' => $this->ecoBusinessActivity->business_name,
            'commercial_registration_number' => $this->ecoBusinessActivity->commercial_registration_number,
            'identity_number' => $this->ecoBusinessActivity->identity_number,
            'owner_name' => $this->ecoBusinessActivity->owner_name,
            'national_identity_numbers' => $this->ecoBusinessActivity->national_identity_numbers,
            'tax_certificate_number' => $this->ecoBusinessActivity->tax_certificate_number,
        ];
    }
}
