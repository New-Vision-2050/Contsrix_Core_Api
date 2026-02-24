<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Handlers;

use Modules\Project\TermSetting\Commands\UpdateTermSettingCommand;
use Modules\Project\TermSetting\Repositories\TermSettingRepository;

class UpdateTermSettingHandler
{
    public function __construct(
        private TermSettingRepository $repository,
    ) {
    }

    public function handle(UpdateTermSettingCommand $updateTermSettingCommand)
    {
        $this->repository->updateTermSetting($updateTermSettingCommand->getId(), $updateTermSettingCommand->toArray());
    }
}
