<?php

declare(strict_types=1);

namespace Modules\SubEntity\Commands;

class UpdateSuperEntityRegistrationCommand
{
    public function __construct(
        private string $id,
        private array $registrationForms,
        private bool $isRegistrable,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getRegistrationForms(): array
    {
        return $this->registrationForms;
    }

    public function toArray(): array
    {
        return [
            'registration_forms' => $this->registrationForms,
            'is_registrable' => $this->isRegistrable,
        ];
    }
}
