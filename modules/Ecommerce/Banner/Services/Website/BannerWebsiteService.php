<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Services\Website;

use Modules\Ecommerce\Banner\Models\Banner;
use Modules\Ecommerce\Banner\Repositories\BannerRepository;
use Ramsey\Uuid\UuidInterface;

class BannerWebsiteService
{
    public function __construct(
        private BannerRepository $repository,
    ) {
    }

    public function list(int $page = 1, int $perPage = 10, ?string $type = null): array
    {
        $conditions = ['is_active' => true];
        
        if ($type) {
            $conditions['type'] = $type;
        }

        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
            conditions: $conditions
        );
    }

    public function get(UuidInterface $id): Banner
    {
        $banner = $this->repository->getBanner($id);
        
        if (!$banner->is_active) {
            abort(404, 'Banner not found');
        }
        
        return $banner;
    }
}

