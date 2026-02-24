<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Handlers;

use Modules\Project\TermSetting\Repositories\TermSettingRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteTermSettingHandler
{
    public function __construct(
        private TermSettingRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteTermSetting($id);
    }
}
