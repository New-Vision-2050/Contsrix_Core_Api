<?php

declare(strict_types=1);

namespace Modules\Company\CompanyField\Presenters;

use Modules\Company\CompanyField\Models\CompanyField;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CompanyFieldPresenter extends AbstractPresenter
{
    private CompanyField $companyField;

    public function __construct(CompanyField $companyField)
    {
        $this->companyField = $companyField;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->companyField->id,
            'name' => $this->companyField->name,
            'description' => $this->companyField->description
        ];
    }
}
