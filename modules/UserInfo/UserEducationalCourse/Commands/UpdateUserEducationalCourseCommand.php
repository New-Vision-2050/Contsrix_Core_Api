<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Commands;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;

class UpdateUserEducationalCourseCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $company_id,
        private string $global_id,
<<<<<<< HEAD
        private string $company_name,
        private string $authority,
        private string $name,
        private string $institute,
        private string $certificate,
        private string $date_obtain,
        private string $date_end,
        public UploadedFile $file
=======
        private ?string $company_name,
        private ?string $authority,
        private ?string $name,
        private ?string $institute,
        private ?string $certificate,
        private ?string $date_obtain,
        private ?string $date_end,
        public ?UploadedFile $file
>>>>>>> 4d33c9eb (merge roles with subscription)
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return array_filter([
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
            'company_name' => $this->company_name,
            'authority' => $this->authority,
            'name' => $this->name,
            'institute' => $this->institute,
            'certificate' => $this->certificate,
            'date_obtain' => $this->date_obtain,
            'date_end' => $this->date_end,
        ]);
    }
}
