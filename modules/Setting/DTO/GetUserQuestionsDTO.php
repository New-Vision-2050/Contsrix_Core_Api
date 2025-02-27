<?php

namespace Modules\Setting\DTO;

use Ramsey\Uuid\UuidInterface;

class GetUserQuestionsDTO
{
    public function __construct(
        private readonly UuidInterface $userId
    ) {}

    public function getUserId(): UuidInterface
    {
        return $this->userId;
    }

}
