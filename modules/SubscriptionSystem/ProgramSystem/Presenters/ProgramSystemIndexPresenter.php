<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Presenters;

use Modules\SubscriptionSystem\ProgramSystem\Models\ProgramSystem;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProgramSystemIndexPresenter extends AbstractPresenter
{
    private ProgramSystem $programSystem;

    public function __construct(ProgramSystem $programSystem)
    {
        $this->programSystem = $programSystem;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->programSystem->id,
            'name' => $this->programSystem->name,
            'is_active' => $this->programSystem->is_active,

            'company_fields_count' => $this->programSystem->companyFields->count(),
            'features_count' => $this->programSystem->features->count(),
            'business_types_count' => $this->programSystem->businessTypes->count(),
            'packages_count' => 0//$this->programSystem->packages->count(),
        ];
    }
}
