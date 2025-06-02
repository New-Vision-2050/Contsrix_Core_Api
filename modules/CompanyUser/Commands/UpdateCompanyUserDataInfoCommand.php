<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateCompanyUserDataInfoCommand
{
    public function __construct(
        public string $name,
        public ?string $nickname,
        public ?string $gender,
        public ?string $birthdate_gregorian,
        public ?string $birthdate_hijri,
        public int $is_default,
        public string $nationality,
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'nickname' => $this->nickname,
            'gender' => $this->gender,
            'birthdate_gregorian' => $this->birthdate_gregorian,
            'birthdate_hijri' => $this->birthdate_hijri,
            'is_default' => $this->is_default,
            'nationality' => $this->nationality,
        ];
    }
}
