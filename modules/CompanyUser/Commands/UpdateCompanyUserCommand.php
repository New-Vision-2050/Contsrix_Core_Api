<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateCompanyUserCommand
{
    public function __construct(
        private UuidInterface $id,
        public string $name,
        public string $email,
        public string $country_id,
        public string $phone,
        public ? string $border_number ,
        public ? string $residence,
        public ? string $identity,
        public ? string $passport,
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
        return [
            'name' => $this->name,
            'email' => $this->email,
            'country_id' => $this->country_id,
            'phone' => $this->phone,
            'border_number' => $this->border_number,
            'residence' => $this->residence,
            "identity"=>$this->identity,
            "passport"=>$this->passport,
        ];
    }
}
