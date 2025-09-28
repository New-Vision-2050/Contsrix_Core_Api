<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Handlers\Dashboard;

use Modules\Ecommerce\EcoAppSetting\Repositories\EcoAppSettingRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoAppSettingDashboardHandler
{
    public function __construct(
        private EcoAppSettingRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id): void
    {
        $this->repository->deleteEcoAppSetting($id);
    }
}
