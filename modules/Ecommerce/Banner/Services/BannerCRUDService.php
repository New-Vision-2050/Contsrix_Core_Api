<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Services;

use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use Modules\Ecommerce\Banner\DTO\CreateBannerDTO;
use Modules\Ecommerce\Banner\Models\Banner;
use Modules\Ecommerce\Banner\Repositories\BannerRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class BannerCRUDService
{
    use HasExportService;

    public function __construct(
        private BannerRepository $repository,
    ) {
    }

    public function create(CreateBannerDTO $createBannerDTO, ?UploadedFile $bannerImage = null): Banner
    {
        $banner = $this->repository->createBanner($createBannerDTO->toArray());
        
        // Handle image upload if provided
        if ($bannerImage) {
            $banner->addMediaFromRequest('banner_image')
                ->toMediaCollection('banner_image');
        }
        
        // Refresh the model to get updated media
        $banner->refresh();
        
        return $banner;
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Banner
    {
        return $this->repository->getBanner(
            id: $id,
        );
    }

    public function toggleStatus(UuidInterface $id): Banner
    {
        $banner = $this->repository->getBanner($id);
        
        // Toggle the is_active status
        $newStatus = !$banner->is_active;
        
        return $this->repository->updateBanner($id, ['is_active' => $newStatus]);
    }
}
