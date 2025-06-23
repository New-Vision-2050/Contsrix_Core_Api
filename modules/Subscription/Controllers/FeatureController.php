<?php

declare(strict_types=1);

namespace Modules\Subscription\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\RoleAndPermission\Presenters\PermissionPresenter;
use Modules\Subscription\Requests\GetFeaturePermissionsRequest;
use Modules\Subscription\Services\FeatureCRUDService;

class FeatureController extends Controller
{
    public function __construct(
        private FeatureCRUDService $featureService
    ) {
    }

    /**
     * Get non-redundant permissions for a set of features
     *
     * @param GetFeaturePermissionsRequest $request
     * @return JsonResponse
     */
    public function getFeaturePermissions(GetFeaturePermissionsRequest $request): JsonResponse
    {
        $featureIds = $request->getFeatureIds();
        $permissions = $this->featureService->getNonRedundantPermissionsByFeatures($featureIds);


        return Json::items(PermissionPresenter::collection($permissions), message: 'Permissions retrieved successfully');
    }
}
