<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Repositories;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use Modules\Subscription\CompanyAccessProgram\DTO\CreateCompanyAccessProgramDTO;
use Modules\Subscription\CompanyAccessProgram\Commands\UpdateCompanyAccessProgramCommand;

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
        return DB::transaction(function () use ($createCompanyAccessProgramDTO) {
            $program = $this->model->create([
                'name' => $createCompanyAccessProgramDTO->name,
                'is_active' => true
            ]);

            // Recursively collect all program and sub-entity IDs
            [$programIds, $subEntityIds] = $this->collectAllIds($createCompanyAccessProgramDTO->rawPrograms);

            // Sync programs
            if (!empty($programIds)) {
                $program->programs()->sync($programIds);
            }

            // Sync sub_entities
            if (!empty($subEntityIds)) {
                $program->subEntities()->sync($subEntityIds);
            }

            // Sync other relations
            if (!empty($createCompanyAccessProgramDTO->companyFields)) {
                $program->companyFields()->sync($createCompanyAccessProgramDTO->companyFields);
            }

            if (!empty($createCompanyAccessProgramDTO->companyTypes)) {
                $program->companyTypes()->sync($createCompanyAccessProgramDTO->companyTypes);
            }

            if (!empty($createCompanyAccessProgramDTO->countries)) {
                $program->countries()->sync($createCompanyAccessProgramDTO->countries);
            }

            return $program;
        });
    }

    /**
     * @param \Modules\Subscription\CompanyAccessProgram\DTO\ProgramPayloadDTO[] $programDTOs
     * @return array{0: array<string>, 1: array<string>}
     */
    private function collectAllIds(array $programDTOs): array
    {
        $programIds = [];
        $subEntityIds = [];

        foreach ($programDTOs as $dto) {
            $programIds[] = $dto->id;
            $subEntityIds = array_merge($subEntityIds, $dto->subEntities);

            if (!empty($dto->children)) {
                [$childProgramIds, $childSubEntityIds] = $this->collectAllIds($dto->children);
                $programIds = array_merge($programIds, $childProgramIds);
                $subEntityIds = array_merge($subEntityIds, $childSubEntityIds);
            }
        }

        return [array_unique($programIds), array_unique($subEntityIds)];
    }

    public function updateCompanyAccessProgram(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function updateCompanyAccessProgramWithRelations(UpdateCompanyAccessProgramCommand $command): CompanyAccessProgram
    {
        return DB::transaction(function () use ($command) {
            $program = $this->findOneByOrFail(['id' => $command->getId()->toString()]);

            $program->update(['name' => $command->getName()]);

            // Recursively collect all program and sub-entity IDs
            [$programIds, $subEntityIds] = $this->collectAllIds($command->getPrograms());

            // Sync programs and sub-entities
            $program->programs()->sync($programIds);
            $program->subEntities()->sync($subEntityIds);

            // Sync other relations
            $program->companyFields()->sync($command->getCompanyFields());
            $program->companyTypes()->sync($command->getCompanyTypes());
            $program->countries()->sync($command->getCountries());

            return $program;
        });
    }

    public function deleteCompanyAccessProgram(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getPackageFormMeta(string $id): CompanyAccessProgram
    {
        return $this->model->where('id', $id)
            ->with('companyFields:id,name', 'countries:id,name,currency,currency_name,currency_symbol', 'companyTypes:id,name')->firstOrFail();
    }

    public function paginated(
        array $conditions = [],
        int $page = 1,
        int $perPage = 15,
        string $orderBy = 'created_at',
        string $sortBy = 'desc'
    ) {
        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all());
        } else {
            $query = $this->model->newQuery();
        }

        // Simple column filters
        if (isset($conditions['is_active'])) {
            $query->where('is_active', $conditions['is_active']);
        }

        if (!empty($conditions['name'])) {
            $query->where('name', 'LIKE', '%' . $conditions['name'] . '%');
        }

        // // Relational filter
        if (!empty($conditions['company_fields'])) {
            $query->whereHas('companyFields', function ($q) use ($conditions) {
                $q->whereIn('company_fields.id', $conditions['company_fields']);
            });
        }

        $query->withCount(['programs', 'subEntities', 'companyFields', 'packages']);

        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->orderBy($orderBy, $sortBy)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);
        // dd($paginatedData);
        return [
            'pagination' => $paginationArray['pagination'],
            'data' => $paginatedData,
        ];
    }

    public function counts(): array
    {
        $totalPrograms = $this->model::count();

        $activePrograms = $this->model::where('is_active', true)->count();

        $distinctCompanyFields = DB::table('company_access_program_field')
            ->distinct('company_field_id')
            ->count('company_field_id');

        $activePackages = \Modules\Subscription\Package\Models\Package::where('is_active', true)->count();

        return [
            'total_company_access_programs' => $totalPrograms,
            'active_company_access_programs' => $activePrograms,
            'company_fields' => $distinctCompanyFields,
            'active_packages' => $activePackages,
        ];
    }
}
