<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\DTO;

use Illuminate\Http\UploadedFile;
use Modules\WebsiteCMS\SocialMediaLink\Enums\SocialMediaType;

class CreateSocialMediaLinkDTO
{
    public function __construct(
        public readonly SocialMediaType $type,
        public readonly string $link,
        public readonly ?UploadedFile $icon = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'link' => $this->link,
        ];
    }
}
