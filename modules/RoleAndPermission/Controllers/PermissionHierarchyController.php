<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Controllers;

use BasePackage\Shared\Controllers\BaseController;
use BasePackage\Shared\Presenters\Json;
use Modules\RoleAndPermission\Services\PermissionHierarchyService;
use Illuminate\Http\JsonResponse;

class PermissionHierarchyController
{
    public function __construct(
        private PermissionHierarchyService $permissionHierarchyService
    ) {
    }

    /**
     * Get permissions parsed from permission names following program.subEntity.action pattern
     *
     * @return JsonResponse
     */
    public function getPermissionsFromNames(): JsonResponse
    {
        $permissions = $this->permissionHierarchyService->excludePrograms(["subscription"])->getPermissionsFromNames();

        return Json::items($permissions);
    }

    /**
     * Get detailed permissions with action information
     *
     * @return JsonResponse
     */
    public function getDetailedPermissions(): JsonResponse
    {
        $permissions = $this->permissionHierarchyService->excludePrograms(["subscription","users","companies"])->getDetailedPermissionsHierarchy();

        return Json::items($permissions);
    }
}
