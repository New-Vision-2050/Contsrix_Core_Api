<?php

declare(strict_types=1);

namespace Modules\SubEntity\Commands;

class UpdateSuperEntityRegistrableConfigCommand
{
    public function __construct(
        private string $id,
        private bool $registrable,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getIsRegistrable(): bool
    {
        return $this->registrable;
    }

    public function toArray(): array
    {
        return [
            'is_registrable' => $this->registrable,
        ];
    }
}
