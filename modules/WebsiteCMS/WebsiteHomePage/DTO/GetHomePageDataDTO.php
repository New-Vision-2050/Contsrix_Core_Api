<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\DTO;

class GetHomePageDataDTO
{
    public function __construct(
        public readonly int $limit = 3,
    ) {
    }
}
