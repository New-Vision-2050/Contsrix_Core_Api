<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserAbout\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateUserAboutDTO
{
    public function __construct(
        public string $about_me,
        public string $company_id,
        public string $global_id,
    ) {
    }

    public function toArray(): array
    {
        return [
            'about_me' => $this->about_me,
            'company_id'=> $this->company_id,
            'global_id'=> $this->global_id,
        ];
    }
}
