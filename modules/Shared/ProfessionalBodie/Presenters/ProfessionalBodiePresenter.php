<?php

declare(strict_types=1);

namespace Modules\Shared\ProfessionalBodie\Presenters;

use Modules\Shared\ProfessionalBodie\Models\ProfessionalBodie;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProfessionalBodiePresenter extends AbstractPresenter
{
    private ProfessionalBodie $professionalBodie;

    public function __construct(ProfessionalBodie $professionalBodie)
    {
        $this->professionalBodie = $professionalBodie;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->professionalBodie->id,
            'name' => $this->professionalBodie->name,
        ];
    }
}
