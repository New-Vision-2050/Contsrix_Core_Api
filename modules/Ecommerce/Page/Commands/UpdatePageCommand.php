<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdatePageCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?array $description = null,
        private ?string $type = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getDescription(): ?array
    {
        return $this->description;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->type !== null) {
            $data['type'] = $this->type;
        }

        return $data;
    }
}
