<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateCategoryWebsiteCMSCommand
{
    public function __construct(
        private UuidInterface $id,
        private array $name,
        private string $category_type,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): array
    {
        return $this->name;
    }

    public function getCategoryType(): string
    {
        return $this->category_type;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'category_type' => $this->category_type,
        ];
    }
}
