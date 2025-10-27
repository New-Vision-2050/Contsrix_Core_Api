<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Presenters;

use Modules\Ecommerce\Banner\Models\Banner;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;
class BannerPresenter extends AbstractPresenter
{
    private Banner $banner;

    public function __construct(Banner $banner)
    {
        $this->banner = $banner;
    }

    protected function present(bool $isListing = false): array
    {
        $media = $this->banner->getFirstMedia('banner_image');
        
        return [
            'id' => $this->banner->id,
            'url' => $this->banner->url,
            'type' => $this->banner->type,
            'is_active' => (int) $this->banner->is_active,
            'image' => $media ? (new MediaPresenter($media))->getData() : null,
            'created_at' => $this->banner->created_at,
            'updated_at' => $this->banner->updated_at,
        ];
    }
}
