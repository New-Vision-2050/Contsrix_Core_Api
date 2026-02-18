<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateContractorContractSettingCommand;
use Modules\Project\ProjectType\Models\ContractorContractSetting;
use Modules\Project\ProjectType\Services\ContractorContractSettingService;

class UpdateContractorContractSettingHandler
{
    public function __construct(
        private readonly ContractorContractSettingService $service
    ) {
    }

    public function handle(UpdateContractorContractSettingCommand $command): ContractorContractSetting
    {
        return $this->service->update($command->projectTypeId, $command->dto);
    }
}
