<?php

declare(strict_types=1);

namespace Modules\UserInfo\Social\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\CompanyUser\Models\CompanyUser;
use Ramsey\Uuid\UuidInterface;

/**
 * @property CompanyUser $model
 * @method Social findOneOrFail($id)
 * @method Social findOneByOrFail(array $data)
 */
class SocialRepository extends BaseRepository
{
    public function __construct(CompanyUser $model)
    {
        parent::__construct($model);
    }

    public function getSocialList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getSocial(UuidInterface $id): CompanyUser
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createSocial(array $data): CompanyUser
    {
        return $this->create($data);
    }

    public function updateSocial(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteSocial(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
