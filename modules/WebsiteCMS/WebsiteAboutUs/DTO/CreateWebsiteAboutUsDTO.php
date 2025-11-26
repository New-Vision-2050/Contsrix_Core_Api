<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\DTO;

use Illuminate\Http\UploadedFile;

class CreateWebsiteAboutUsDTO
{
    public function __construct(
        public readonly array $title,
        public readonly array $description,
        public readonly bool $is_certificates,
        public readonly bool $is_approvals,
        public readonly bool $is_companies,
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
