<?php

declare(strict_types=1);

namespace Modules\Shared\SocialIcon\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateSocialIconCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?string $name = null,
        private ?string $webIcon = null,
        private ?string $mobileIcon = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getWebIcon(): ?string
    {
        return $this->webIcon;
    }

    public function getMobileIcon(): ?string
    {
        return $this->mobileIcon;
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->webIcon !== null) {
            $data['web_icon'] = $this->webIcon;
        }

        if ($this->mobileIcon !== null) {
            $data['mobile_icon'] = $this->mobileIcon;
        }

        return $data;
    }
}
