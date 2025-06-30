<?php

declare(strict_types=1);

namespace Modules\Company\BusinessType\Presenters;

use Modules\Company\BusinessType\Models\BusinessType;
use BasePackage\Shared\Presenters\AbstractPresenter;

class BusinessTypePresenter extends AbstractPresenter
{
    private BusinessType $businessType;

    public function __construct(BusinessType $businessType)
    {
        $this->businessType = $businessType;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->businessType->id,
            'name' => $this->businessType->name,
        ];
    }
}
