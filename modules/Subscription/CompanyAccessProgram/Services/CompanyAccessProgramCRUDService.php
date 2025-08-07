<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Services;

use Illuminate\Support\Collection;
use Modules\Program\Models\Program;
use Modules\RoleAndPermission\Repositories\PermissionRepository;
use Modules\RoleAndPermission\Services\PermissionHierarchyService;
use Modules\SubEntity\Models\SubEntity;
use Modules\Subscription\CompanyAccessProgram\DTO\CreateCompanyAccessProgramDTO;
use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use Modules\Subscription\CompanyAccessProgram\Repositories\CompanyAccessProgramRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class CompanyAccessProgramCRUDService
{
    public function __construct(
        private CompanyAccessProgramRepository $repository,
        private PermissionHierarchyService $permissionHierarchyService,
        private PermissionRepository $permissionRepository,
    ) {
    }

    public function create(CreateCompanyAccessProgramDTO $createCompanyAccessProgramDTO): CompanyAccessProgram
    {
        return $this->repository->createCompanyAccessProgram($createCompanyAccessProgramDTO);
    }

    public function list(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        return $this->repository->paginated(
            conditions: $filters,
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): CompanyAccessProgram
    {
        return $this->repository->getCompanyAccessProgram(
            id: $id,
        );
    }

    public function getPackageFormMeta(string $id): CompanyAccessProgram
    {
        return $this->repository->getPackageFormMeta(
            id: $id,
        );
    }

    public function counts(): array
    {
        return $this->repository->counts();
    }

    /**
     * Get filtered company access programs for export
     *
     * @param array $filters Array of filters
     * @return Collection
     */
    public function getForExport(array $filters = []): Collection
    {
        return $this->repository->getForExport($filters);
    }

    /**
     * Get programs in hierarchical structure with their sub-entities
     *
     * @param string $id
     * @return array
     */
    public function getProgramsHierarchy(string $id): array
    {
        $companyAccessProgram = $this->repository->getCompanyAccessProgram(Uuid::fromString($id));

        // Get the active program and sub-entity IDs for this CompanyAccessProgram
        $activeProgramIds = $companyAccessProgram->programs->pluck('program_id')->unique()->toArray();
        $activeSubEntityIds = $companyAccessProgram->subEntities->pluck('sub_entity_id')->unique()->toArray();

        // Get the full hierarchy of all permissions
        $fullHierarchy = $this->permissionHierarchyService->excludePrograms(["subscription","users","companies","program-management","permissions"])->getDetailedPermissionsHierarchy();

        // Instead of filtering, we will add an 'is_active' flag to each item.
        foreach ($fullHierarchy as &$program) {
            foreach ($program['sub_entities'] as &$subEntity) {
                $subEntity['is_active'] = in_array($subEntity['id'], $activeSubEntityIds);
            }
        }

        return $fullHierarchy;
    }


}
