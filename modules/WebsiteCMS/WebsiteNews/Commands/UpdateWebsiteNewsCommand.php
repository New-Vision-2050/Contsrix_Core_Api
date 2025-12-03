<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteNews\Commands;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Http\UploadedFile;

class UpdateWebsiteNewsCommand
{
    public function __construct(
        private UuidInterface $id,
        private array $title,
        private array $content,
        private ?UploadedFile $main_image,
        private ?UploadedFile $thumbnail,
        private string $category_website_cms_id,
        private string $publish_date,
        private ?string $end_date = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getTitle(): array
    {
        return $this->title;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function getMainImage(): ?UploadedFile
    {
        return $this->main_image;
    }

    public function getThumbnail(): ?UploadedFile
    {
        return $this->thumbnail;
    }

    public function getCategoryWebsiteCmsId(): string
    {
        return $this->category_website_cms_id;
    }

    public function getPublishDate(): string
    {
        return $this->publish_date;
    }

    public function getEndDate(): ?string
    {
        return $this->end_date;
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
}
