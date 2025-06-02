<?php

declare(strict_types=1);

namespace Modules\SubEntity\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateSubEntityCommand
{
    public function __construct(
        private UuidInterface $id,
        private bool $isRegistrable,
        private ?array $childrenAllowedRegistrationForms
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'is_registrable' => $this->isRegistrable,
            'children_allowed_registration_forms' => $this->childrenAllowedRegistrationForms
        ];
    }
}
