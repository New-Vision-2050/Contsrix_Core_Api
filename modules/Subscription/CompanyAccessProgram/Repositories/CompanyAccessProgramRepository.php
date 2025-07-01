<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Repositories;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Database\Eloquent\Collection;
use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use Modules\Subscription\CompanyAccessProgram\DTO\CreateCompanyAccessProgramDTO;

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

    public function createCompanyAccessProgram(CreateCompanyAccessProgramDTO $createCompanyAccessProgramDTO): CompanyAccessProgram
    {
        $program = $this->model->create([
            'name' => $createCompanyAccessProgramDTO->name,
        ]);

        // Sync programs
        if (!empty($createCompanyAccessProgramDTO->programs)) {
            $program->programs()->sync($createCompanyAccessProgramDTO->programs);
        }

        // Sync sub_entities
        if (!empty($createCompanyAccessProgramDTO->sub_entities)) {
            $program->subEntities()->sync($createCompanyAccessProgramDTO->subEntities);
        }

        // Sync company fields
        if (!empty($createCompanyAccessProgramDTO->companyFields)) {
            $program->companyFields()->sync($createCompanyAccessProgramDTO->companyFields);
        }

        // Sync company types
        if (!empty($createCompanyAccessProgramDTO->companyTypes)) {
            $program->companyTypes()->sync($createCompanyAccessProgramDTO->companyTypes);
        }

        // Sync countries
        if (!empty($createCompanyAccessProgramDTO->countries)) {
            $program->countries()->sync($createCompanyAccessProgramDTO->countries);
        }

        return $program;
    }

    public function updateCompanyAccessProgram(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteCompanyAccessProgram(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getPackageFormMeta(string $id): CompanyAccessProgram {
        return $this->model->where('id', $id)
        ->with('companyFields:id,name', 'countries:id,name,currency,currency_name,currency_symbol', 'companyTypes:id,name')->firstOrFail();
    }
}
