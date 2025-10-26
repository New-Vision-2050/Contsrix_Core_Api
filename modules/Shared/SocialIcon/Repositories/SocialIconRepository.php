<?php

declare(strict_types=1);

namespace Modules\Shared\SocialIcon\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\SocialIcon\Models\SocialIcon;
use App\Traits\HasExport;

/**
 * @property SocialIcon $model
 * @method SocialIcon findOneOrFail($id)
 * @method SocialIcon findOneByOrFail(array $data)
 */
class SocialIconRepository extends BaseRepository
{
    use HasExport;

    public function __construct(SocialIcon $model)
    {
        parent::__construct($model);
    }

    public function getSocialIconList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getSocialIcon(UuidInterface $id): SocialIcon
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createSocialIcon(array $data): SocialIcon
    {
        return $this->create($data);
    }

    public function updateSocialIcon(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteSocialIcon(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
