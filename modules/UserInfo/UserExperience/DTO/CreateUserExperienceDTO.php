<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserExperience\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateUserExperienceDTO
{
    public function __construct(
        public string $company_id,
        public string $global_id,

        public string $job_name,
        public string $training_from,
        public string $training_to,
        public string $company_name,
        public string $about,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id,
            'global_id' => $this->global_id,
            'job_name' => $this->job_name,
            'training_from' => $this->training_from,
            'training_to' => $this->training_to,
            'company_name' => $this->company_name,
            'about' => $this->about,
        ];
    }
}
