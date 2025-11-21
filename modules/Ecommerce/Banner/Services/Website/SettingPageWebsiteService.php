<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Services\Website;

use Modules\Ecommerce\Banner\Models\SettingPage;
use Modules\Ecommerce\Banner\Repositories\SettingPageRepository;

class SettingPageWebsiteService
{
    public function __construct(
        private SettingPageRepository $repository,
    ) {
    }

    public function getByType(string $type): ?SettingPage
    {
        return $this->repository->findByType($type);
    }
}

