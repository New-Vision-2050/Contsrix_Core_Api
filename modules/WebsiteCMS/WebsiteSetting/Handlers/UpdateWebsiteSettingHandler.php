<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\Handlers;

use Modules\WebsiteCMS\WebsiteSetting\Commands\UpdateWebsiteSettingCommand;
use Modules\WebsiteCMS\WebsiteSetting\Repositories\WebsiteSettingRepository;
use Modules\WebsiteCMS\WebsiteSetting\Services\WebsiteSettingUploadService;
use Modules\WebsiteCMS\WebsiteSetting\Models\WebsiteSetting;

class UpdateWebsiteSettingHandler
{
    public function __construct(
        private WebsiteSettingRepository $repository,
        private WebsiteSettingUploadService $uploadService,
    ) {
    }

    public function handle(UpdateWebsiteSettingCommand $updateWebsiteSettingCommand): WebsiteSetting
    {
        $websiteSetting = $this->repository->updateWebsiteSetting(
            $updateWebsiteSettingCommand->getId(), 
            $updateWebsiteSettingCommand->toArray()
        );
        
        // Handle logo upload if provided
        if ($updateWebsiteSettingCommand->getLogo()) {
            $this->uploadService->uploadLogo($websiteSetting, $updateWebsiteSettingCommand->getLogo());
        }
        
        return $websiteSetting;
    }
}
