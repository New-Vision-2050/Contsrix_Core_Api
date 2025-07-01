<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Subscription\Package\Handlers\DeletePackageHandler;
use Modules\Subscription\Package\Handlers\UpdatePackageHandler;
use Modules\Subscription\Package\Presenters\PackagePresenter;
use Modules\Subscription\Package\Requests\CreatePackageRequest;
use Modules\Subscription\Package\Requests\DeletePackageRequest;
use Modules\Subscription\Package\Requests\GetPackageListRequest;
use Modules\Subscription\Package\Requests\GetPackageRequest;
use Modules\Subscription\Package\Requests\UpdatePackageRequest;
use Modules\Subscription\Package\Services\PackageCRUDService;
use Ramsey\Uuid\Uuid;

class PackageController extends Controller
{
    public function __construct(
        private PackageCRUDService $packageService,
        private UpdatePackageHandler $updatePackageHandler,
        private DeletePackageHandler $deletePackageHandler,
    ) {
    }

    public function index(GetPackageListRequest $request): JsonResponse
    {
        $list = $this->packageService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(PackagePresenter::collection($list['data']), paginationSettings: $list['pagination']);
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

        return Json::item( $presenter->getData());
    }

    public function delete(DeletePackageRequest $request): JsonResponse
    {
        $this->deletePackageHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
