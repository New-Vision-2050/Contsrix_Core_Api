<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationType\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateCompanyRegistrationTypeCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private int $type,
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
    public function getType(): ?int
    {
        return $this->type;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type
        ]);
    }
}
