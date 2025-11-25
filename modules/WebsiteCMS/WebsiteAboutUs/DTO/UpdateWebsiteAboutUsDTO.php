<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\DTO;

use Illuminate\Http\UploadedFile;

class UpdateWebsiteAboutUsDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly int $is_certificates,
        public readonly int $is_approvals,
        public readonly int $is_companies,
        public readonly array $about_me,
        public readonly array $vision,
        public readonly array $target,
        public readonly array $slogan,
        public readonly ?UploadedFile $main_image = null,
        public readonly ?array $project_types = null,
        public readonly ?array $attachments = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'is_certificates' => $this->is_certificates,
            'is_approvals' => $this->is_approvals,
            'is_companies' => $this->is_companies,
            'about_me' => $this->about_me,
            'vision' => $this->vision,
            'target' => $this->target,
            'slogan' => $this->slogan,
        ];
    }
}
