<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\CompanyUser\Models\CompanyUser;

/**
 * @property CompanyUser $model
 * @method CompanyUser findOneOrFail($id)
 * @method CompanyUser findOneByOrFail(array $data)
 */
class CompanyUserRepository extends BaseRepository
{
    public function __construct(CompanyUser $model)
    {
        parent::__construct($model);
    }

    public function getCompanyUserList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getCompanyUser(UuidInterface $id): CompanyUser
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCompanyUser(array $data): CompanyUser
    {
        return $this->create($data);
    }

    public function updateCompanyUser(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteCompanyUser(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
