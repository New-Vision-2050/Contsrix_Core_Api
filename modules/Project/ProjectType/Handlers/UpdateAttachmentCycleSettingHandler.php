<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateAttachmentCycleSettingCommand;
use Modules\Project\ProjectType\Models\AttachmentCycleSetting;
use Modules\Project\ProjectType\Services\AttachmentCycleSettingService;

class UpdateAttachmentCycleSettingHandler
{
    public function __construct(
        private readonly AttachmentCycleSettingService $service
    ) {
    }

    public function handle(UpdateAttachmentCycleSettingCommand $command): AttachmentCycleSetting
    {
        return $this->service->update($command->projectTypeId, $command->dto);
    }
}
