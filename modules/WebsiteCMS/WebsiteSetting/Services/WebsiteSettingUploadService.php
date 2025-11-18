<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\Services;

use Illuminate\Http\UploadedFile;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\WebsiteCMS\WebsiteSetting\Models\WebsiteSetting;

class WebsiteSettingUploadService
{
    private FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function uploadLogo(WebsiteSetting $websiteSetting, ?UploadedFile $logo): void
    {
        if ($logo) {
            // Clear existing logo if any
            $websiteSetting->clearMediaCollection('logo');
            
            // Upload new logo
            $this->fileUploadService->uploadFile(
                $websiteSetting,
                $logo,
                'website-settings/logos',
                'logo',
                'public'
            );
        }
    }
}
