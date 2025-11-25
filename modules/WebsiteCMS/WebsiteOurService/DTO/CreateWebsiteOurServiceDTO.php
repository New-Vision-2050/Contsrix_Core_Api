<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\DTO;

class CreateWebsiteOurServiceDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly array $departments,
        public readonly int $status = 1,
    ) {
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
