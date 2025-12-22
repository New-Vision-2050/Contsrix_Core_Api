<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteService\Commands;

use Illuminate\Http\UploadedFile;

class UpdateWebsiteServiceCommand
{
    public function __construct(
        private string        $id,
        private array         $name,
        private ?UploadedFile $main_image,
        private ?UploadedFile $icon,
        private string        $category_website_cms_id,
        private ?string        $reference_number,
        private array         $description,
        private ?array        $previous_work = null,
        private int           $status = 1
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): array
    {
        return $this->name;
    }

    public function getMainImage(): ?UploadedFile
    {
        return $this->main_image;
    }

    public function getIcon(): ?UploadedFile
    {
        return $this->icon;
    }

    public function getCategoryWebsiteCmsId(): string
    {
        return $this->category_website_cms_id;
    }

    public function getReferenceNumber(): string
    {
        return $this->reference_number;
    }

    public function getDescription(): array
    {
        return $this->description;
    }

    public function getPreviousWork(): ?array
    {
        return $this->previous_work;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category_website_cms_id' => $this->category_website_cms_id,
            'reference_number' => $this->reference_number,
            'description' => $this->description,
            'previous_work' => $this->previous_work,
            "status" => $this->status
        ];
    }
}
