<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Services;

use Modules\Subscription\CompanyAccessProgram\Models\CompanyAccessProgram;
use Modules\Subscription\CompanyAccessProgram\Repositories\CompanyAccessProgramRepository;
use Modules\RoleAndPermission\Services\PermissionHierarchyService;
use Modules\RoleAndPermission\Models\Permission;
use Ramsey\Uuid\UuidInterface;

class CompanyAccessProgramPermissionsService
{
    public function __construct(
        private PermissionHierarchyService $permissionHierarchyService,
        private CompanyAccessProgramRepository $repository
    ) {
    }

    /**
     * Get detailed permissions hierarchy with is_selected field for a company access program
     *
     * @param UuidInterface $companyAccessProgramId
     * @return array
     */
    public function getDetailedPermissionsHierarchy(UuidInterface $companyAccessProgramId): array
    {
        // Get the company access program
        $companyAccessProgram = $this->repository->getCompanyAccessProgram($companyAccessProgramId);

        // Get all permissions hierarchy (complete tree)
        $allPermissions = $this->permissionHierarchyService->getDetailedPermissionsHierarchy();

        // Get the selected programs and sub-entities from the original API
        $selectedData = $this->getSelectedProgramsAndSubEntities($companyAccessProgram);

        // Add is_selected field to the hierarchy
        foreach ($allPermissions as &$program) {
            // Check if this program is selected
            $program['is_selected'] = isset($selectedData['programs'][$program['slug']]);

            foreach ($program['sub_entities'] as &$subEntity) {
                // Check if this sub-entity is selected
                $subEntity['is_selected'] = isset($selectedData['sub_entities'][$subEntity['slug']]);

                // Add actions with is_selected field
                if (isset($subEntity['actions'])) {
                    $actionsWithSelection = [];
                    foreach ($subEntity['actions'] as $action) {
                        $permissionName = $program['slug'] . '.' . $subEntity['slug'] . '.' . $action;
                        $actionsWithSelection[] = [
                            'name' => $action,
                            'permission_name' => $permissionName,
                            'is_selected' => $this->isPermissionSelected($permissionName, $selectedData)
                        ];
                    }
                    $subEntity['actions'] = $actionsWithSelection;
                }
            }
        }

        // Return both company access program data and permissions hierarchy
        return [

                'id' => $companyAccessProgram->id,
                'name' => $companyAccessProgram->name,
                'status' => $companyAccessProgram->is_active ? true : false,
                'company_fields' => $companyAccessProgram->companyFields,
                'company_types' => $companyAccessProgram->companyTypes,
                'countries' => $companyAccessProgram->countries,
                'sub_entities' => $allPermissions


        ];
    }

    /**
     * Get selected programs and sub-entities from the company access program
     *
     * @param CompanyAccessProgram $companyAccessProgram
     * @return array
     */
    private function getSelectedProgramsAndSubEntities(CompanyAccessProgram $companyAccessProgram): array
    {
        $selectedData = [
            'programs' => [],
            'sub_entities' => [],
            'permissions' => []
        ];

        // Get program IDs from pivot table (same as original service)
        $programRecords = $companyAccessProgram->programs;
        $programIds = $programRecords->pluck('program_id')->unique();

        // Get sub-entity IDs from pivot table (same as original service)
        $subEntityRecords = $companyAccessProgram->subEntities;
        $subEntityIds = $subEntityRecords->pluck('sub_entity_id')->unique();

        // Mark programs as selected
        foreach ($programIds as $programId) {
            $selectedData['programs'][$programId] = true;
        }

        // Mark sub-entities as selected
        foreach ($subEntityIds as $subEntityId) {
            $selectedData['sub_entities'][$subEntityId] = true;
        }

        // Get permissions for selected programs and sub-entities
        foreach ($programIds as $programId) {
            foreach ($subEntityIds as $subEntityId) {
                // Check if this sub-entity belongs to this program (same logic as original)
                if ($this->subEntityBelongsToProgram($subEntityId, $programId)) {
                    // Get permissions for this program.sub-entity combination
                    $permissions = Permission::where('name', 'like', $programId . '.' . $subEntityId . '.%')
                        ->where('status', true)
                        ->pluck('name')
                        ->toArray();

                    foreach ($permissions as $permission) {
                        $selectedData['permissions'][$permission] = true;
                    }
                }
            }
        }

        return $selectedData;
    }

    /**
     * Determine if a sub-entity belongs to a program (same logic as original service)
     */
    private function subEntityBelongsToProgram(string $subEntityId, string $programId): bool
    {
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
        return true; // This will show all sub-entities under each program
    }

    /**
     * Check if a specific permission is selected
     *
     * @param string $permissionName
     * @param array $selectedData
     * @return bool
     */
    private function isPermissionSelected(string $permissionName, array $selectedData): bool
    {
        return isset($selectedData['permissions'][$permissionName]);
    }
}
