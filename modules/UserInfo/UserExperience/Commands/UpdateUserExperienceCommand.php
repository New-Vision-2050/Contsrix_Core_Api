<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserExperience\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateUserExperienceCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $company_id,
        private string $global_id,

        private string $job_name,
        private string $training_from,
        private string $training_to,
        private string $company_name,
        private string $about,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }


    public function toArray(): array
    {
        return array_filter([
            'company_id'=> $this->company_id,
            'global_id'=> $this->global_id,

            'job_name'=> $this->job_name,
            'training_from'=> $this->training_from,
            'training_to'=> $this->training_to,
            'company_name'=> $this->company_name,
            'about'=> $this->about,
        ]);
    }
}
