<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Handlers;

use Illuminate\Http\UploadedFile;
use Modules\Ecommerce\Banner\Commands\UpdateBannerCommand;
use Modules\Ecommerce\Banner\Repositories\BannerRepository;

class UpdateBannerHandler
{
    public function __construct(
        private BannerRepository $repository,
    ) {
    }

    public function handle(UpdateBannerCommand $updateBannerCommand, ?UploadedFile $bannerImage = null)
    {
        $banner = $this->repository->updateBanner($updateBannerCommand->getId(), $updateBannerCommand->toArray());
        
        // Handle image upload if provided
        if ($bannerImage) {
            // Clear existing image first
            $banner->clearMediaCollection('banner_image');
            
            // Add new image
            $banner->addMedia($bannerImage)
                ->toMediaCollection('banner_image');
        }
        
        return $banner;
    }
}
