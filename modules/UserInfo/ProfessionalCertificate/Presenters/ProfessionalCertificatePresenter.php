<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Presenters;

use Modules\UserInfo\ProfessionalCertificate\Models\ProfessionalCertificate;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProfessionalCertificatePresenter extends AbstractPresenter
{
    private ProfessionalCertificate $professionalCertificate;

    public function __construct(ProfessionalCertificate $professionalCertificate)
    {
        $this->professionalCertificate = $professionalCertificate;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->professionalCertificate->id,
            'professional_bodie_id' => $this->professionalCertificate->professional_bodie_id,
            'professional_bodie_name' => $this->professionalCertificate->professional_bodie?->name,
            'accreditation_name' => $this->professionalCertificate->accreditation_name,
            'accreditation_number' => $this->professionalCertificate->accreditation_number,
            'accreditation_degree' => $this->professionalCertificate->accreditation_degree,
            'date_obtain' => $this->professionalCertificate->date_obtain,
            'date_end' => $this->professionalCertificate->date_end,
        ];
    }
}
