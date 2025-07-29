<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Controllers;

use Modules\RoleAndPermission\Services\PermissionLookupService;
use Modules\Subscription\Package\Presenters\PackageSimplePresenter;
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
use Modules\Subscription\Package\Requests\ExportPackageRequest;
use Modules\Subscription\Package\Handlers\UpdatePackageStatusHandler;
use Modules\Subscription\Package\Requests\UpdatePackageStatusRequest;
use Modules\Subscription\Package\Requests\AttachPackageFeaturesRequest;
use Modules\Subscription\Package\Requests\SyncPackagePermissionsRequest;
use Modules\Subscription\Package\Requests\AssignPackagesToCompanyRequest;
use Modules\Subscription\Package\Models\Package;
use Modules\Subscription\Package\Presenters\PackageWithPermissionsPresenter;
use Modules\Subscription\Package\Services\PackageAssignmentService;
use Modules\Subscription\Package\Exports\PackageExport;
use Maatwebsite\Excel\Facades\Excel;

class PackageController extends Controller
{
    public function __construct(
        private PackageCRUDService         $packageService,
        private UpdatePackageHandler       $updatePackageHandler,
        private UpdatePackageStatusHandler $updatePackageStatusHandler,
        private DeletePackageHandler       $deletePackageHandler,
        private PackageAssignmentService   $assignmentService,
    )
    {
    }

    public function index(GetPackageListRequest $request): JsonResponse
    {
        $filters = [];

        if ($request->has('status')) {
            $filters['is_active'] = $request->boolean('status');
        }

        if ($request->has('name')) {
            $filters['name'] = $request->get('name');
        }




        if ($request->has('company_access_program_id')) {
            $filters['company_access_program_id'] = $request->get('company_access_program_id');
        }



        if ($request->has('company_field_id')) {
            $filters['company_field_id'] = $request->input('company_field_id');
        }

        $list = $this->packageService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10),
            $filters
        );

        return Json::items(PackagePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }
    public function list(GetPackageListRequest $request): JsonResponse
    {
        $filters = [];

        if ($request->has('status')) {
            $filters['is_active'] = $request->boolean('status');
        }

        if ($request->has('name')) {
            $filters['name'] = $request->get('name');
        }




        if ($request->has('company_access_program_id')) {
            $filters['company_access_program_id'] = $request->get('company_access_program_id');
        }



        if ($request->has('company_field_id')) {
            $filters['company_field_id'] = $request->input('company_field_id');
        }

        $list = $this->packageService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10),
            $filters
        );

        return Json::items(PackageSimplePresenter::collection($list['data']), paginationSettings: $list['pagination']);
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
        $updatedItem = $this->packageService->update($request->createUpdatePackageDTO());

        $presenter = new PackagePresenter($updatedItem);

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
        $permissions = $request->validated('permissions');
        $limits = $request->getPermissionLimits();

        $this->packageService->syncPermissions($package, $permissions, $limits);

        return Json::success('Permissions synced successfully with limits.');
    }

    public function getPermissions(Package $package)
    {
        $package->load('permissions');
        return app(PermissionLookupService::class)->getPermissionsForPackage($package->id);
        $presenter = new PackageWithPermissionsPresenter($package);

        return Json::item($presenter->getData());
    }

    /**
     * Assign multiple packages to a company with automatic limit handling.
     */
    public function assignPackagesToCompany(AssignPackagesToCompanyRequest $request): JsonResponse
    {
        try {
            $result = $this->assignmentService->assignPackagesToCompany(
                $request->validated('company_id'),
                $request->validated('package_ids')
            );

            return Json::success($result['message'], $result);
        } catch (\Exception $e) {
            return Json::error('Failed to assign packages to company: ' . $e->getMessage());
        }
    }

    /**
     * Export packages to a file
     *
     * @param ExportPackageRequest $request
     */
    public function export(ExportPackageRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'packages.' . $format;

        $filters = $request->getFilters();

        return Excel::download(new PackageExport($this->packageService, $filters), $fileName);
    }
}
