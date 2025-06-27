<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;

/**
 * @property CompanyAccessProgram $model
 * @method CompanyAccessProgram findOneOrFail($id)
 * @method CompanyAccessProgram findOneByOrFail(array $data)
 */
class CompanyAccessProgramRepository extends BaseRepository
{
    public function __construct(CompanyAccessProgram $model)
    {
        parent::__construct($model);
    }

    public function getCompanyAccessProgramList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getCompanyAccessProgram(UuidInterface $id): CompanyAccessProgram
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCompanyAccessProgram(array $data): CompanyAccessProgram
    {
        return $this->create($data);
    }

    public function updateCompanyAccessProgram(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteCompanyAccessProgram(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
