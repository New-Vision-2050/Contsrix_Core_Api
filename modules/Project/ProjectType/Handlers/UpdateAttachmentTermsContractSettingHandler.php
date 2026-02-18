<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateAttachmentTermsContractSettingCommand;
use Modules\Project\ProjectType\Models\AttachmentTermsContractSetting;
use Modules\Project\ProjectType\Services\AttachmentTermsContractSettingService;

class UpdateAttachmentTermsContractSettingHandler
{
    public function __construct(
        private readonly AttachmentTermsContractSettingService $service
    ) {
    }

    public function handle(UpdateAttachmentTermsContractSettingCommand $command): AttachmentTermsContractSetting
    {
        return $this->service->update($command->projectTypeId, $command->dto);
    }
}
