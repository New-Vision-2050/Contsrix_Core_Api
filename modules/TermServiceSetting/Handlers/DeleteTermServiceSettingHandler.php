<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\Handlers;

use Modules\TermServiceSetting\Repositories\TermServiceSettingRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteTermServiceSettingHandler
{
    public function __construct(
        private TermServiceSettingRepository $repository,
    ) {
    }

    public function handle(int $id)
    {
        $this->repository->deleteTermServiceSetting($id);
    }
}
