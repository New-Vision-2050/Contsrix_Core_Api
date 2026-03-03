<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Handlers;

use Modules\Project\TermSetting\Repositories\TermSettingRepository;

class DeleteTermSettingHandler
{
    public function __construct(
        private TermSettingRepository $repository,
    ) {
    }

    public function handle(int $id)
    {
        $this->repository->deleteTermSetting($id);
    }
}
