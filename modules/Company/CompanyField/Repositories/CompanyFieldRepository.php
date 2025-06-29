<?php

declare(strict_types=1);

namespace Modules\Company\CompanyField\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\CompanyField\Models\CompanyField;

/**
 * @property CompanyField $model
 * @method CompanyField findOneOrFail($id)
 * @method CompanyField findOneByOrFail(array $data)
 */
class CompanyFieldRepository extends BaseRepository
{
    public function __construct(CompanyField $model)
    {
        parent::__construct($model);
    }

    public function getCompanyFieldList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getCompanyField(UuidInterface $id): CompanyField
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCompanyField(array $data): CompanyField
    {
        return $this->create($data);
    }

    public function updateCompanyField(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteCompanyField(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function countFieldsUsedInPrograms(): int
    {
        return $this->model->whereHas('programSystems')->count();
    }
}
