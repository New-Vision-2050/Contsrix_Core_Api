<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteNews\DTO;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Http\UploadedFile;

class CreateWebsiteNewsDTO
{
    public function __construct(
        public readonly array $title,
        public readonly array $content,
        public readonly UploadedFile $main_image,
        public readonly UploadedFile $thumbnail,
        public readonly string $category_website_cms_id,
        public readonly string $publish_date,
        public readonly ?string $end_date = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'category_website_cms_id' => $this->category_website_cms_id,
            'publish_date' => $this->publish_date,
            'end_date' => $this->end_date,
        ];
    }

    public function getMainImage(): UploadedFile
    {
        return $this->main_image;
    }

    public function getThumbnail(): UploadedFile
    {
        return $this->thumbnail;
    }
}
