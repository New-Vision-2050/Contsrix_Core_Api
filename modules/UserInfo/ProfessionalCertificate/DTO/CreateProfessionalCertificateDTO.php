<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\DTO;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;

class CreateProfessionalCertificateDTO
{
    public function __construct(
        public string $company_id,
        public string $global_id,
        public ?string $professional_bodie_id,
        public ?string $accreditation_name,
        public ?string $accreditation_number,
        public ?int $professional_degree_id,
        public ?string $date_obtain,
        public ?string $date_end,
        public ?UploadedFile $file
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id'=> $this->company_id,
            'global_id'=> $this->global_id,
            'professional_bodie_id'=> $this->professional_bodie_id,
            'accreditation_name'=> $this->accreditation_name,
            'accreditation_number'=> $this->accreditation_number,
            'professional_degree_id'=> $this->professional_degree_id,
            'date_obtain'=> $this->date_obtain,
            'date_end'=> $this->date_end,
        ];
    }
}
