<?php

declare(strict_types=1);

namespace Modules\Company\CompanyType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\CompanyType\Models\CompanyType;

/**
 * @property CompanyType $model
 * @method CompanyType findOneOrFail($id)
 * @method CompanyType findOneByOrFail(array $data)
 */
class CompanyTypeRepository extends BaseRepository
{
    public function __construct(CompanyType $model)
    {
        parent::__construct($model);
    }

    public function getCompanyTypeList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getCompanyType(UuidInterface $id): CompanyType
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCompanyType(array $data): CompanyType
    {
        return $this->create($data);
    }

    public function updateCompanyType(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteCompanyType(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
