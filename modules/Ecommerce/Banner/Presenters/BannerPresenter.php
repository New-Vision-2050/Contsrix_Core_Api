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
            'company_id' => $this->banner->company_id,
            'url' => $this->banner->url,
            'type' => $this->banner->type,
            'title' => $this->banner->title,
            'description' => $this->banner->description,
            'is_active' => (int) $this->banner->is_active,
            'image' => $media ? (new MediaPresenter($media))->getData() : null,
        ];
    }
}
