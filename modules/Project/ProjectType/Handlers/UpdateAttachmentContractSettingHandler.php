<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateAttachmentContractSettingCommand;
use Modules\Project\ProjectType\Models\AttachmentContractSetting;
use Modules\Project\ProjectType\Services\AttachmentContractSettingService;

class UpdateAttachmentContractSettingHandler
{
    public function __construct(
        private readonly AttachmentContractSettingService $service
    ) {
    }

    public function handle(UpdateAttachmentContractSettingCommand $command): AttachmentContractSetting
    {
        return $this->service->update($command->projectTypeId, $command->dto);
    }
}
