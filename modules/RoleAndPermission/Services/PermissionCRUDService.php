<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyCore\Models\Company;
use Modules\RoleAndPermission\DTO\CreatePermissionDTO;
use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Repositories\PermissionRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class PermissionCRUDService
{
    public function __construct(
        private PermissionRepository $repository,
    ) {
    }

    public function create(CreatePermissionDTO $createPermissionDTO): Permission
    {
         return $this->repository->createPermission($createPermissionDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }


    public function listPermissionAsLookup()
    {
        $permissions = $this->repository->getPermissionsWithoutPagination();
        return $this->makePermissionAsLookup($permissions);

    }


    public function makePermissionAsLookup($permissions)
    {
        $modified = [];
        foreach ($permissions as $permission) {

            // Extract the permission name parts
            $nameParts = explode('.', $permission->name);

            // Initialize the translated name
            $translatedName = '';

            // Apply translation logic like the Blade template
            if (count($nameParts) >= 2) {
                // Skip the first part (module name) and translate the rest
                for ($i = count($nameParts) - 1; $i >= 1; $i--) {
                    if ($i == 1 && str_contains($nameParts[$i], "*")) {
                        $resources = explode('*', $nameParts[$i]);
                        $translatedName .=" ". $resources[0];
                        break;
                    }
                    $translatedName .= ($translatedName ? ' ' : '') . __('names.' . $nameParts[$i]);
                }
            } elseif (count($nameParts) == 1) {
                $translatedName = __('names.' . $nameParts[0]);
            } else {
                $translatedName = __('names.' . $permission->name);
            }
            $parts = explode('.', $permission->name);
            $modified[] = [
                "id" => $permission->id,
                "key" => $permission->name,
                "type" => $parts[count($parts) - 1],
                "name" => $translatedName,
            ];
        }

        // First group by the first part of the name (module)
        $groupedByModule = collect($modified)->groupBy(function ($query) {
            $parts = explode('.', $query["key"]);
            return isset($parts[0]) ? __('names.' . $parts[0]): 'other';
        });

        // Then for each module group, group again by the second part (action)
        $nestedGroups = $groupedByModule->map(function ($group, $module) {
            return collect($group)->groupBy(function ($item) {
                $parts = explode('.', $item["key"]);

                return isset($parts[1]) ? __('names.' . $parts[1]) : 'other';
            });
        })->toArray();
        return $nestedGroups;
    }

    public function get(UuidInterface $id): Permission
    {
        return $this->repository->getPermission(
            id: $id,
        );
    }

    /**
     * Copy all permissions from one company to another
     *
     * @param UuidInterface|string|null $sourceCompanyId The source company ID to copy from (if null, uses the first company)
     * @param UuidInterface|string $targetCompanyId The target company ID to copy to
     * @return Collection The collection of created permission instances
     */
    public function copyPermissionsToCompany($targetCompanyId,$sourceCompanyId = null): Collection
    {
        // Convert string IDs to UuidInterface if needed
        if (is_string($targetCompanyId)) {
            $targetCompanyId = Uuid::fromString($targetCompanyId);
        }

        // Find the source company (default to the first company if not specified)
        $sourceCompanyPermissions = Permission::query();

        if ($sourceCompanyId) {
            if (is_string($sourceCompanyId)) {
                $sourceCompanyId = Uuid::fromString($sourceCompanyId);
            }
            $sourceCompanyPermissions->where('company_id', $sourceCompanyId->toString());
        } else {
            // Find the first company's permissions if no source company ID is provided
            $firstCompany = Company::orderBy('created_at')->first();
            if ($firstCompany) {
                $sourceCompanyPermissions->where('company_id', $firstCompany->id);
            } else {
                // If no companies exist, use global permissions (where company_id is null)
                $sourceCompanyPermissions->whereNull('company_id');
            }
        }

        // Get all permissions from source company
        $permissions = $sourceCompanyPermissions->get();
        $createdPermissions = collect();

        // Copy each permission to the target company
        foreach ($permissions as $permission) {
            // Create new permission record for the target company with the same name and guard
            $newPermission = Permission::updateOrCreate(
                [
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                    'company_id' => $targetCompanyId->toString()
                ],
                [
                    'id' => Uuid::uuid4()->toString(),
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                    'company_id' => $targetCompanyId->toString(),
                    'status' => $permission->status ?? true
                ]
            );

            $createdPermissions->push($newPermission);
        }

        return $createdPermissions;
    }

    /**
     * Get permissions for a specific company
     *
     * @param UuidInterface|string $companyId The company ID
     * @return Collection Collection of permissions belonging to the company
     */
    public function getPermissionsByCompany($companyId): Collection
    {
        if (is_string($companyId)) {
            $companyId = Uuid::fromString($companyId);
        }

        return Permission::where('company_id', $companyId->toString())->get();
    }

    /**
     * Set the status of a permission.
     *
     * @param UuidInterface|string $id The ID of the permission.
     * @param bool $status The new status.
     * @return Permission
     */
    public function setStatus($id, bool $status): Permission
    {
        if (is_string($id)) {
            $id = Uuid::fromString($id);
        }

        $permission = $this->repository->getPermission($id);
        $this->repository->update($id, ['status' => $status]);

        return $permission->refresh();
    }
}
