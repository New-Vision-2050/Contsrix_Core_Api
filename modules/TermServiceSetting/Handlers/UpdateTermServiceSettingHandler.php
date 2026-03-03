<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\Handlers;

use Modules\TermServiceSetting\Commands\UpdateTermServiceSettingCommand;
use Modules\TermServiceSetting\Repositories\TermServiceSettingRepository;

class UpdateTermServiceSettingHandler
{
    public function __construct(
        private TermServiceSettingRepository $repository,
    ) {
    }

    public function handle(UpdateTermServiceSettingCommand $updateTermServiceSettingCommand)
    {
        $this->repository->updateTermServiceSetting($updateTermServiceSettingCommand->getId(), $updateTermServiceSettingCommand->toArray());
    }
}
