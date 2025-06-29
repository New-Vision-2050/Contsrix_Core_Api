<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Presenters;

use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CompanyAccessProgramPresenter extends AbstractPresenter
{
    private CompanyAccessProgram $companyAccessProgram;

    public function __construct(CompanyAccessProgram $companyAccessProgram)
    {
        $this->companyAccessProgram = $companyAccessProgram;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->companyAccessProgram->id,
            'name' => $this->companyAccessProgram->name,
        ];
    }
}
