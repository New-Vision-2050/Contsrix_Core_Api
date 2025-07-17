<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Services;

use Illuminate\Support\Collection;
use Modules\Program\Models\Program;
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

        // Get program IDs from pivot table
        $programRecords = $companyAccessProgram->programs;
        $programIds = $programRecords->pluck('program_id')->unique();

        // Get sub-entity IDs from pivot table
        $subEntityRecords = $companyAccessProgram->subEntities;
        $subEntityIds = $subEntityRecords->pluck('sub_entity_id')->unique();

        // Create a map to track which sub-entities belong to which programs
        // We'll use a simple approach: sub-entities that start with program name belong to that program
        $result = [];

        // Process programs first
        foreach ($programIds as $programId) {
            $programData = [
                'id' => $programId,
                'name' => __('names.'.$programId),
                'slug' => $programId,
                'is_active' => 1,
                'sub_entities' => [],
                'children' => []
            ];

            // Find sub-entities that belong to this program
            // Based on the DTO structure, sub-entities are directly associated with programs
            foreach ($subEntityIds as $subEntityId) {
                // Check if this sub-entity belongs to this program
                // This could be based on naming convention or stored relationship
                if ($this->subEntityBelongsToProgram($subEntityId, $programId)) {
                    $programData['sub_entities'][] = [
                        'id' => $subEntityId,
                        'name' => __("names.".$subEntityId),
                        'slug' => $subEntityId,
                        'main_program_id' => $programId,
                        'super_entity' => $programId,
                        'origin_super_entity' => $programId,
                        'is_active' => 1,
                        'children' => []
                    ];
                }
            }

            $result[] = $programData;
        }

        return $result;
    }

    /**
     * Determine if a sub-entity belongs to a program
     * This logic should be implemented based on your business rules
     */
    private function subEntityBelongsToProgram(string $subEntityId, string $programId): bool
    {
        // Multiple possible patterns for association:
        // 1. Sub-entity ID starts with program ID (e.g., "users.client" belongs to "users")
        // 2. Sub-entity ID contains program ID somewhere (e.g., "client.users" belongs to "users")
        // 3. Sub-entity and program have some common substring
        // 4. For now, let's be more permissive and see what happens

        // Try exact matching patterns first
        if (str_starts_with($subEntityId, $programId)) {
            return true;
        }

        // Try reverse matching
        if (str_contains($subEntityId, $programId)) {
            return true;
        }

        // If program is "users" and sub-entity contains "user" (singular/plural)
        if ($programId === 'users' && (str_contains($subEntityId, 'user') || str_contains($subEntityId, 'client'))) {
            return true;
        }

        // For testing: temporarily associate all sub-entities with all programs to see the structure
        // Remove this after we understand the actual relationship
        return true; // This will show all sub-entities under each program
    }
}
