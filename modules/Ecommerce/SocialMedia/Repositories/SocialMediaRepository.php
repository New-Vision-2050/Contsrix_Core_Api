<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\SocialMedia\Models\SocialMedia;
use App\Traits\HasExport;

/**
 * @property SocialMedia $model
 * @method SocialMedia findOneOrFail($id)
 * @method SocialMedia findOneByOrFail(array $data)
 */
class SocialMediaRepository extends BaseRepository
{
    use HasExport;

    public function __construct(SocialMedia $model)
    {
        parent::__construct($model);
    }

    public function getSocialMediaList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getSocialMedia(UuidInterface $id): SocialMedia
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createSocialMedia(array $data): SocialMedia
    {
        return $this->create($data);
    }

    public function updateSocialMedia(UuidInterface $id, array $data): SocialMedia
    {
        $socialMedia = $this->getSocialMedia($id);
        $socialMedia->update($data);
        return $socialMedia->fresh();
    }

    public function deleteSocialMedia(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function toggleStatus(UuidInterface $id): SocialMedia
    {
        $socialMedia = $this->getSocialMedia($id);
        $newStatus = !$socialMedia->is_active;
        $socialMedia->update(['is_active' => $newStatus]);
        return $socialMedia->fresh();
    }
}
