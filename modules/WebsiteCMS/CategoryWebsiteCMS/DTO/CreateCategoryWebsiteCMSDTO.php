<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\DTO;

use Ramsey\Uuid\UuidInterface;

readonly class CreateCategoryWebsiteCMSDTO
{
    public function __construct(
        public array $name,
        public string $category_type,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'category_type' => $this->category_type,
        ];
    }

    public function getName(): array
    {
        return $this->name;
    }

    public function getCategoryType(): string
    {
        return $this->category_type;
    }
}
