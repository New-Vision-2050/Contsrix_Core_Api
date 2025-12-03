<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Commands;

use Illuminate\Http\UploadedFile;
use Modules\WebsiteCMS\SocialMediaLink\Enums\SocialMediaType;
use Ramsey\Uuid\UuidInterface;

class UpdateSocialMediaLinkCommand
{
    public function __construct(
        private UuidInterface $id,
        private SocialMediaType $type,
        private string $link,
        private ?UploadedFile $icon = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getType(): SocialMediaType
    {
        return $this->type;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getIcon(): ?UploadedFile
    {
        return $this->icon;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'link' => $this->link,
        ];
    }
}
