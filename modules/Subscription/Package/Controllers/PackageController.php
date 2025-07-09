<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Controllers;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\Subscription\Package\Requests\GetPackageRequest;
use Modules\Subscription\Package\Presenters\PackagePresenter;
use Modules\Subscription\Package\Services\PackageCRUDService;
use Modules\Subscription\Package\Handlers\DeletePackageHandler;
use Modules\Subscription\Package\Handlers\UpdatePackageHandler;
use Modules\Subscription\Package\Requests\CreatePackageRequest;
use Modules\Subscription\Package\Requests\DeletePackageRequest;
use Modules\Subscription\Package\Requests\UpdatePackageRequest;
use Modules\Subscription\Package\Requests\GetPackageListRequest;
use Modules\Subscription\Package\Handlers\UpdatePackageStatusHandler;
use Modules\Subscription\Package\Requests\UpdatePackageStatusRequest;
use Modules\Subscription\Package\Requests\AttachPackageFeaturesRequest;
use Modules\Subscription\Package\Requests\SyncPackagePermissionsRequest;
use Modules\Subscription\Package\Models\Package;
use Modules\Subscription\Package\Presenters\PackageWithPermissionsPresenter;

class PackageController extends Controller
{
    public function __construct(
        private PackageCRUDService $packageService,
        private UpdatePackageHandler $updatePackageHandler,
        private UpdatePackageStatusHandler $updatePackageStatusHandler,
        private DeletePackageHandler $deletePackageHandler,
    ) {
    }

    public function index(GetPackageListRequest $request): JsonResponse
    {
        $filters = [];

        if($request->has('status')) {
            $filters['is_active'] = $request->boolean('status');
        }

        if($request->has('name')) {
            $filters['name'] = $request->get('name');
        }

        if($request->has('company_fields')) {
            $filters['company_fields'] = $request->input('company_fields');
        }

        $list = $this->packageService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
            $filters
        );

        return Json::items(PackagePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function counts(GetPackageListRequest $request): JsonResponse
    {
        $counts = $this->packageService->counts();

        return Json::item($counts);
    }

    public function show(GetPackageRequest $request): JsonResponse
    {
        $item = $this->packageService->get(Uuid::fromString($request->route('id')));

        $presenter = new PackagePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreatePackageRequest $request): JsonResponse
    {
        $createdItem = $this->packageService->create($request->createCreatePackageDTO());

        $presenter = new PackagePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdatePackageRequest $request): JsonResponse
    {
        $command = $request->createUpdatePackageCommand();
        $this->updatePackageHandler->handle($command);

        $item = $this->packageService->get($command->getId());

        $presenter = new PackagePresenter($item);

        return Json::item($presenter->getData());
    }

    public function updateStatus(UpdatePackageStatusRequest $request): JsonResponse
    {
        $command = $request->createUpdatePackageStatusCommand();
        $this->updatePackageStatusHandler->handle($command);

        $item = $this->packageService->get($command->getId());

        $presenter = new PackagePresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeletePackageRequest $request): JsonResponse
    {
        $this->deletePackageHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function attachFeatures(AttachPackageFeaturesRequest $request): JsonResponse
    {
        $dto = $request->createAttachPackageFeaturesDTO();

        // Transform to array for batch insert
        $features = array_map(fn($f) => $f->toArray(), $dto->features);

        $this->packageService->attachFeatures(
            $dto->packageId,
            $features,
        );

        return Json::success('Features attached successfully');
    }

    public function syncPermissions(SyncPackagePermissionsRequest $request, Package $package): JsonResponse
    {

        $this->packageService->syncPermissions($package, $request->validated('permissions'));

        return Json::success('Permissions synced successfully.');
    }

    public function getPermissions(Package $package): JsonResponse
    {
        $package->load('permissions');
        $presenter = new PackageWithPermissionsPresenter($package);

        return Json::item($presenter->getData());
    }
}
