<?php

declare(strict_types=1);

namespace Modules\UserInfo\Qualification\Commands;

use Illuminate\Validation\Rules\In;
use Ramsey\Uuid\UuidInterface;

class UpdateQualificationCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $country_id,
        private string $university_id,
        private string $academic_qualification_id,
        private string $academic_specialization_id,
        private int $study_rate,
        private string $graduation_date,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return array_filter([
            'country_id' => $this->country_id,
            'university_id' => $this->university_id,
            'academic_qualification_id' => $this->academic_qualification_id,
            'academic_specialization_id' => $this->academic_specialization_id,
            'study_rate' => $this->study_rate,
            'graduation_date' => $this->graduation_date,
        ]);
    }
}
