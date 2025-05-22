<?php

declare(strict_types=1);

namespace Modules\SubEntity\Commands;

class UpdateSuperEntityRegistrationFormsConfigCommand
{
    public function __construct(
        private string $id,
        private array $registrationForms,
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
        ];
    }
}
