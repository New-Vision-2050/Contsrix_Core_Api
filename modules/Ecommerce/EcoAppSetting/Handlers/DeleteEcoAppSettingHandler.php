<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Handlers;

use Modules\Ecommerce\EcoAppSetting\Repositories\EcoAppSettingRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoAppSettingHandler
{
    public function __construct(
        private EcoAppSettingRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoAppSetting($id);
    }
}
