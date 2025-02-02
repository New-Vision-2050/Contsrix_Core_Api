<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;

/**
 * @property CompanyRegistrationType $model
 * @method CompanyRegistrationType findOneOrFail($id)
 * @method CompanyRegistrationType findOneByOrFail(array $data)
 */
class CompanyRegistrationTypeRepository extends BaseRepository
{
    public function __construct(CompanyRegistrationType $model)
    {
        parent::__construct($model);
    }

    public function getCompanyRegistrationTypeList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getCompanyRegistrationType(UuidInterface $id): CompanyRegistrationType
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCompanyRegistrationType(array $data): CompanyRegistrationType
    {
        return $this->create($data);
    }

    public function updateCompanyRegistrationType(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteCompanyRegistrationType(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
