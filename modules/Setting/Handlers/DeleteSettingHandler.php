<?php

declare(strict_types=1);

namespace Modules\Setting\Handlers;

use Modules\Setting\Repositories\SettingRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteSettingHandler
{
    public function __construct(
        private SettingRepository $repository,
    ) {
    }

    public function handle(string $key)
    {
        $this->repository->deleteSetting($key);
    }
}
