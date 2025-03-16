<?php

namespace Modules\Setting\DTO;

use Ramsey\Uuid\UuidInterface;

class GetUserQuestionsDTO
{
    public function __construct(
        private readonly string $identifier
    ) {}

    public function getIdentifier()
    {
        return $this->identifier;
    }

}
