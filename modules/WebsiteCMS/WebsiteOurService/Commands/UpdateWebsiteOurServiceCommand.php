<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateWebsiteOurServiceCommand
{
    public function __construct(
        private UuidInterface $id,
        private array $title,
        private array $description,
        private array $departments,
        private int $status = 1,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getTitle(): array
    {
        return $this->title;
    }

    public function getDescription(): array
    {
        return $this->description;
    }

    public function getDepartments(): array
    {
        return $this->departments;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}
