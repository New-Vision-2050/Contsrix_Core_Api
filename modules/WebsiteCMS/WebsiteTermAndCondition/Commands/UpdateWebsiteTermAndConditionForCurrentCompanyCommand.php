<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateWebsiteTermAndConditionForCurrentCompanyCommand
{
    public function __construct(
        private string $content,
    ) {
    }



    public function getContent(): ?string
    {
        return $this->content;
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
        ];
    }
}
