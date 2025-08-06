<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Repositories;

use BasePackage\Shared\Presenters\Json;
use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\DTO\PermissionWidgetsDataDTO;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function Laravel\Prompts\text;


/**
 * @property Permission $model
 * @method Permission findOneOrFail($id)
 * @method Permission findOneByOrFail(array $data)
 */
class PermissionRepository extends BaseRepository
{
    public function __construct(Permission $model , private CompanyRepository $companyRepository)
    {
        parent::__construct($model);
    }

    public function getPermissionList(?int $page, ?int $perPage = 10)
    {
        $packagesId = $this->companyRepository->getCompany(Uuid::fromString(tenant("id")))->packages()->pluck('id')->toArray();
        if(tenant('is_central_company')){
            $query = $this->model->filter(request()->all());
        }else{
            $query = $this->model->filter(request()->all())->whereHas('packages', function ($q) use ($packagesId) {

                $q->whereIn('packages.id', $packagesId);
            });
        }


        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);
        return array_merge($paginationArray, [
            'data' => $paginatedData
        ]);
    }

    public function getPermissionsWithoutPagination()
    {
        return $this->model->get();
    }

    public function getPermission(UuidInterface $id): Permission
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createPermission(array $data): Permission
    {
        return $this->create($data);
    }

    public function updatePermission(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deletePermission(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Find permission by name.
     */
    public function findByName(string $name): ?Permission
    {
        return $this->model->where('name', $name)
            ->first();
    }

    /**
     * Get permissions filtered by subEntity names where subEntity matches
     * the second part of permission name pattern "programName.subEntity.action"
     */
    public function getPermissionsBySubEntities(array $subEntities)
    {
        if (empty($subEntities)) {
            return collect();
        }

        return $this->model->where(function ($query) use ($subEntities) {
            foreach ($subEntities as $subEntity) {
                // Escape special regex characters in subEntity to treat them as literals
//                $escapedSubEntity = preg_quote($subEntity, '/');
                // Use REGEXP to match exactly the second segment: "anything.subEntity.anything"
                $query->orWhere('name', 'LIKE', "%.".$subEntity."%");
            }
        })->get();
    }

    /**
     * Get permission widgets data including total, main, active, and inactive counts
     */
    public function getPermissionWidgetsData(): PermissionWidgetsDataDTO
    {
        $query = $this->model->query();
        
        $totalPermissions = $query->count();
        $activePermissions = (clone $query)->where('status', 1)->count();
        $inactivePermissions = (clone $query)->where('status', 0)->count();
        
        // Count main permissions (those that don't have 'DYNAMIC.' in their key)
        $mainPermissions = (clone $query)
            ->where('key', 'NOT LIKE', '%DYNAMIC.%')
            ->count();

        return PermissionWidgetsDataDTO::fromArray([
            'total_permissions' => $totalPermissions,
            'total_main_permissions' => $mainPermissions,
            'active_permissions' => $activePermissions,
            'inactive_permissions' => $inactivePermissions,
        ]);
    }
}
