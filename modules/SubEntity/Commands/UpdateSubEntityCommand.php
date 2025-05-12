<?php

declare(strict_types=1);

namespace Modules\SubEntity\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateSubEntityCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private ?string $slug,
        private string $icon,
        private string $mainProgramId,
        private bool $isActive,
        private bool $isRegistrable,

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
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'main_program_id' => $this->mainProgramId,
            'is_active' => $this->isActive,
            'is_registrable' => $this->isRegistrable
        ]);
    }
}
