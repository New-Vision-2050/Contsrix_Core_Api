<?php

declare(strict_types=1);

namespace Modules\Company\RegistrationType\Presenters;

use Modules\Company\RegistrationType\Models\RegistrationType;
use BasePackage\Shared\Presenters\AbstractPresenter;

class RegistrationTypePresenter extends AbstractPresenter
{
    private RegistrationType $RegistrationType;

    public function __construct(RegistrationType $RegistrationType)
    {
        $this->RegistrationType = $RegistrationType;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->RegistrationType->id,
            'name' => $this->RegistrationType->name,
        ];
    }
}
