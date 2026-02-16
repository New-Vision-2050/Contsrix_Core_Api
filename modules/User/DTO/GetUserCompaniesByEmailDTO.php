<?php

declare(strict_types=1);

namespace Modules\User\DTO;

class GetUserCompaniesByEmailDTO
{
    public function __construct(
        public readonly string $email
    ) {
    }
}
