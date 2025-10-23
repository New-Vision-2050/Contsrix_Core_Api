<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\Banner\Models\Banner;
use App\Traits\HasExport;

/**
 * @property Banner $model
 * @method Banner findOneOrFail($id)
 * @method Banner findOneByOrFail(array $data)
 */
class BannerRepository extends BaseRepository
{
    use HasExport;

    public function __construct(Banner $model)
    {
        parent::__construct($model);
    }

    public function getBannerList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getBanner(UuidInterface $id): Banner
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createBanner(array $data): Banner
    {
        return $this->create($data);
    }

    public function updateBanner(UuidInterface $id, array $data): Banner
    {
        $banner = $this->getBanner($id);
        $banner->update($data);
        return $banner->fresh();
    }

    public function deleteBanner(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
