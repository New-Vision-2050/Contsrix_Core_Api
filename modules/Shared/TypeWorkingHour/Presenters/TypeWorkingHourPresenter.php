<?php

declare(strict_types=1);

namespace Modules\Shared\TypeWorkingHour\Presenters;

use Modules\Shared\TypeWorkingHour\Models\TypeWorkingHour;
use BasePackage\Shared\Presenters\AbstractPresenter;

class TypeWorkingHourPresenter extends AbstractPresenter
{
    private TypeWorkingHour $typeWorkingHour;

    public function __construct(TypeWorkingHour $typeWorkingHour)
    {
        $this->typeWorkingHour = $typeWorkingHour;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->typeWorkingHour->id,
            'name' => $this->typeWorkingHour->name,
        ];
    }
}
