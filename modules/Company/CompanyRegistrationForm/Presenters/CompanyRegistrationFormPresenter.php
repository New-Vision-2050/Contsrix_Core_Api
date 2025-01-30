<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationForm\Presenters;

use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CompanyRegistrationFormPresenter extends AbstractPresenter
{
    private CompanyRegistrationForm $companyRegistrationForm;

    public function __construct(CompanyRegistrationForm $companyRegistrationForm)
    {
        $this->companyRegistrationForm = $companyRegistrationForm;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->companyRegistrationForm->id,
            'name' => $this->companyRegistrationForm->name,
        ];
    }
}
