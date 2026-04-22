<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Handlers;

use Modules\Project\ProjectType\Commands\UpdateArchiveLibrarySettingCommand;
use Modules\Project\ProjectType\Models\ArchiveLibrarySetting;
use Modules\Project\ProjectType\Services\ArchiveLibrarySettingService;

class UpdateArchiveLibrarySettingHandler
{
    public function __construct(
        private readonly ArchiveLibrarySettingService $service
    ) {
    }

    public function handle(UpdateArchiveLibrarySettingCommand $command): ArchiveLibrarySetting
    {
        return $this->service->update($command->projectTypeId, $command->dto);
    }
}
