<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateWebsiteTermAndConditionCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $content,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
        ];
    }
}
