<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Presenters;

use Modules\Shared\Media\Presenters\MediaPresenter;
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
        $media = $this->professionalCertificate->getFirstMedia('upload');
        return [
            'id' => $this->professionalCertificate->id,
            'professional_bodie_id' => $this->professionalCertificate->professional_bodie_id,
            'professional_bodie_name' => $this->professionalCertificate->professionalBodie?->name,
            'accreditation_name' => $this->professionalCertificate->accreditation_name,
            'accreditation_number' => $this->professionalCertificate->accreditation_number,
            'professional_degree_id' => $this->professionalCertificate->professional_degree_id,
            'professional_degree_name_ar' => $this->professionalCertificate->professionalDegree?->name_ar,
            'professional_degree_name' => $this->professionalCertificate->professionalDegree?->name_ar,
            'professional_degree_name_en' => $this->professionalCertificate->professionalDegree?->name_en,
            'date_obtain' => $this->professionalCertificate->date_obtain,
            'date_end' => $this->professionalCertificate->date_end,
            "file"=>$media != null ? (new MediaPresenter($media))->getData(): null,
        ];
    }
}
