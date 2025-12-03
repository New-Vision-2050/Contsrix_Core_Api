<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\DTO;

use Ramsey\Uuid\UuidInterface;

class ReplyToContactMessageDTO
{
    public function __construct(
        public readonly UuidInterface $id,
        public readonly int $status,
        public readonly string $replyMessage,
    ) {
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
        ];
    }
}
