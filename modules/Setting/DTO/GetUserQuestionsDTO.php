<?php

namespace Modules\Setting\DTO;

use Ramsey\Uuid\UuidInterface;

class GetUserQuestionsDTO
{
    public function __construct(
        private readonly UuidInterface $userId
    ) {}

    public function getUserId(): int
    {
        return $this->userId;
    }

    public static function from(array $data): self
    {
        return new self(
            userId: $data['user_id']
        );
    }
}
