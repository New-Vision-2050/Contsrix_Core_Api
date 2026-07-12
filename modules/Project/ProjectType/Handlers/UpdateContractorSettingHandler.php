<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateContractorSettingCommand;
use Modules\Project\ProjectType\Models\ContractorSetting;
use Modules\Project\ProjectType\Services\ContractorSettingService;

class UpdateContractorSettingHandler
{
    public function __construct(
        private readonly ContractorSettingService $service
    ) {
    }

    public function handle(UpdateContractorSettingCommand $command): ContractorSetting
    {
        return $this->service->update($command->projectTypeId, $command->dto);
    }
}
