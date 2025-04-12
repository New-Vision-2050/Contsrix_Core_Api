<?php

declare(strict_types=1);

namespace Modules\UserInfo\Social\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateSocialCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?string $whatsapp,
        private ?string $facebook,
        private ?string $telegram,
        private ?string $instagram,
        private ?string $snapchat,
        private ?string $linkedin,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return array_filter([
            "whatsapp"=> $this->whatsapp??"",
            "facebook"=> $this->facebook??"",
            "telegram"=> $this->telegram??"",
            "instagram"=> $this->instagram??"",
            "snapchat"=> $this->snapchat??"",
            "linkedin"=> $this->linkedin??"",
        ]);
    }
}
