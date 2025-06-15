<?php

declare(strict_types=1);

namespace Modules\UserInfo\Biography\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\CompanyUser\Models\CompanyUser;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\Biography\Models\Biography;

/**
 * @property CompanyUser $model
 * @method Biography findOneOrFail($id)
 * @method Biography findOneByOrFail(array $data)
 */
class BiographyRepository extends BaseRepository
{
    public function __construct(CompanyUser $model)
    {
        parent::__construct($model);
    }

    public function getBiographyList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getBiography(UuidInterface $id): CompanyUser
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createBiography(array $data): CompanyUser
    {
        return $this->create($data);
    }

    public function updateBiography(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteBiography(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
