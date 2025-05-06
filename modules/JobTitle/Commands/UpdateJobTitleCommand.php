<?php

declare(strict_types=1);

namespace Modules\JobTitle\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateJobTitleCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private ?UuidInterface $job_type_id = null,
        private ?string $description = null,
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

    public function getJobTypeId(): ?string
    {
        return $this->job_type_id;
    }



    public function getDescription(): ?string
    {
        return $this->description;
    }



    public function toArray(): array
    {
        return [
            'name' => ["ar"=>$this->name,"en"=>$this->name],
            'job_type_id' => $this->job_type_id,
            'description' => $this->description,
        ];
    }
}
