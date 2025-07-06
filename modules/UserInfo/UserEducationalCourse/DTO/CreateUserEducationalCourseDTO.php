<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\DTO;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\UuidInterface;

class CreateUserEducationalCourseDTO
{
    public function __construct(
        public string $company_id,
        public string $global_id,
        public ?string $company_name,
        public ?string $authority,
        public ?string $name,
        public ?string $institute,
        public ?string $certificate,
        public ?string $date_obtain,
        public ?string $date_end,
        public ?UploadedFile $file
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
            'company_name' => $this->company_name,
            'authority' => $this->authority,
            'name' => $this->name,
            'institute' => $this->institute,
            'certificate' => $this->certificate,
            'date_obtain' => $this->date_obtain,
            'date_end' => $this->date_end,
        ];
    }
}
