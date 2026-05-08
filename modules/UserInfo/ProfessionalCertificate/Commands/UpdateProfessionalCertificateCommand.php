<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Commands;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;

class UpdateProfessionalCertificateCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?string $professional_bodie_id,
        private ?string $accreditation_name,
        private ?string $accreditation_number,
        private ?int $professional_degree_id,
        private ?string $date_obtain,
        private ?string $date_end,
        public ?UploadedFile $file
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return array_filter([
            'professional_bodie_id'=> $this->professional_bodie_id,
            'accreditation_name'=> $this->accreditation_name,
            'accreditation_number'=> $this->accreditation_number,
            'professional_degree_id'=> $this->professional_degree_id,
            'date_obtain'=> $this->date_obtain,
            'date_end'=> $this->date_end,
        ]);
    }
}
