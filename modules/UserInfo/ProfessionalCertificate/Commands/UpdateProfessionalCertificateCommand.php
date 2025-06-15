<?php

declare(strict_types=1);

namespace Modules\UserInfo\ProfessionalCertificate\Commands;

<<<<<<< HEAD
use Illuminate\Http\UploadedFile;
=======
>>>>>>> 7be6c72c (merge with stage (first version ))
use Ramsey\Uuid\UuidInterface;

class UpdateProfessionalCertificateCommand
{
    public function __construct(
        private UuidInterface $id,
<<<<<<< HEAD
        private ?string $professional_bodie_id,
        private ?string $accreditation_name,
        private ?string $accreditation_number,
        private ?string $accreditation_degree,
        private ?string $date_obtain,
        private ?string $date_end,
        public ?UploadedFile $file
=======
        private string $professional_bodie_id,
        private string $accreditation_name,
        private string $accreditation_number,
        private string $accreditation_degree,
        private string $date_obtain,
        private string $date_end,
>>>>>>> 7be6c72c (merge with stage (first version ))
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
            'accreditation_degree'=> $this->accreditation_degree,
            'date_obtain'=> $this->date_obtain,
            'date_end'=> $this->date_end,
        ]);
    }
}
