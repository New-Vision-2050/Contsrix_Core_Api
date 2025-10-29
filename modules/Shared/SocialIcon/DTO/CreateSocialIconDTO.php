<?php

declare(strict_types=1);

namespace Modules\Shared\SocialIcon\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateSocialIconDTO
{
    public function __construct(
        public string $name,
        public string $webIcon,
        public string $mobileIcon,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'web_icon' => $this->webIcon,
            'mobile_icon' => $this->mobileIcon,
        ];
    }
}
