<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\RoleAndPermission\Presenters\PermissionPresenter;
use Modules\SubscriptionSystem\Feature\Handlers\DeleteFeatureHandler;
use Modules\SubscriptionSystem\Feature\Handlers\UpdateFeatureHandler;
use Modules\SubscriptionSystem\Feature\Presenters\FeaturePresenter;
use Modules\SubscriptionSystem\Feature\Requests\CreateFeatureRequest;
use Modules\SubscriptionSystem\Feature\Requests\DeleteFeatureRequest;
use Modules\SubscriptionSystem\Feature\Requests\GetFeatureListRequest;
use Modules\SubscriptionSystem\Feature\Requests\GetFeaturePermissionsRequest;
use Modules\SubscriptionSystem\Feature\Requests\GetFeatureRequest;
use Modules\SubscriptionSystem\Feature\Requests\UpdateFeatureRequest;
use Modules\SubscriptionSystem\Feature\Services\FeatureCRUDService;
use Ramsey\Uuid\Uuid;

class FeatureController extends Controller
{
    public function __construct(
        private FeatureCRUDService $featureService,
        private UpdateFeatureHandler $updateFeatureHandler,
        private DeleteFeatureHandler $deleteFeatureHandler,
    ) {
    }

    public function index(GetFeatureListRequest $request): JsonResponse
    {
        $list = $this->featureService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(FeaturePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetFeatureRequest $request): JsonResponse
    {
        $item = $this->featureService->get(Uuid::fromString($request->route('id')));

        $presenter = new FeaturePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateFeatureRequest $request): JsonResponse
    {
        $createdItem = $this->featureService->create($request->createCreateFeatureDTO());

        $presenter = new FeaturePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateFeatureRequest $request): JsonResponse
    {
        $command = $request->createUpdateFeatureCommand();
        $this->updateFeatureHandler->handle($command);

        $item = $this->featureService->get($command->getId());

        $presenter = new FeaturePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteFeatureRequest $request): JsonResponse
    {
        $this->deleteFeatureHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
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
