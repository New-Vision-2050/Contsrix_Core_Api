<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\UserInfo\ProfessionalCertificate\Models\ProfessionalDegree;

class ProfessionalDegreePresenter extends AbstractPresenter
{
    private ProfessionalDegree $professionalDegree;

    public function __construct(ProfessionalDegree $professionalDegree)
    {
        $this->professionalDegree = $professionalDegree;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->professionalDegree->id,
            'name_ar' => $this->professionalDegree->name_ar,
            'name_en' => $this->professionalDegree->name_en,
            'is_active' => $this->professionalDegree->is_active,
        ];
    }
}
