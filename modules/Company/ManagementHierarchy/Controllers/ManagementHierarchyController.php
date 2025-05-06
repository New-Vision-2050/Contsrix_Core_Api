<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use Modules\Company\ManagementHierarchy\Handlers\DeleteManagementHierarchyHandler;
use Modules\Company\ManagementHierarchy\Handlers\MakeBranchMainHandler;
use Modules\Company\ManagementHierarchy\Handlers\UpdateBranchHandler;
use Modules\Company\ManagementHierarchy\Handlers\UpdateManagementHierarchyHandler;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Presenters\DepartmentPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyTreePresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyUserTreePresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementPresenter;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Modules\Company\ManagementHierarchy\Requests\CreateBranchRequest;
use Modules\Company\ManagementHierarchy\Requests\CreateDepartmentRequest;
use Modules\Company\ManagementHierarchy\Requests\CreateManagementHierarchyRequest;
use Modules\Company\ManagementHierarchy\Requests\CreateManagementRequest;
use Modules\Company\ManagementHierarchy\Requests\DeleteManagementHierarchyRequest;
use Modules\Company\ManagementHierarchy\Requests\GetManagementHierarchyListRequest;
use Modules\Company\ManagementHierarchy\Requests\GetManagementHierarchyLookupRequest;
use Modules\Company\ManagementHierarchy\Requests\GetManagementHierarchyRequest;
use Modules\Company\ManagementHierarchy\Requests\MakeBranchMainRequest;
use Modules\Company\ManagementHierarchy\Requests\UpdateBranchRequest;
use Modules\Company\ManagementHierarchy\Requests\UpdateManagementHierarchyRequest;
use Modules\Company\ManagementHierarchy\Services\ManagementHierarchyCRUDService;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\Uuid;

class ManagementHierarchyController extends Controller
{
    public function __construct(
        private ManagementHierarchyCRUDService   $managementHierarchyService,
        private UpdateManagementHierarchyHandler $updateManagementHierarchyHandler,
        private DeleteManagementHierarchyHandler $deleteManagementHierarchyHandler,
        private MakeBranchMainHandler            $makeBranchMainHandler,
        private UpdateBranchHandler              $updateBranchHandler,
        private ManagementHierarchyRepository    $managementHierarchyRepository,
    )
    {
    }

    public function index(GetManagementHierarchyListRequest $request): JsonResponse
    {
        $list = $this->managementHierarchyService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );
        return Json::items(ManagementHierarchyPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function listWithoutPagination(GetManagementHierarchyLookupRequest $request): JsonResponse
    {

        return Json::items(ManagementHierarchyPresenter::collection($this->managementHierarchyService->listWithoutPagination()));
    }

    public function show(GetManagementHierarchyRequest $request): JsonResponse
    {
        $item = $this->managementHierarchyService->get((int)($request->route('id')));

        $presenter = new ManagementHierarchyPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateManagementHierarchyRequest $request): JsonResponse
    {
        $createdItem = $this->managementHierarchyService->create($request->createCreateManagementHierarchyDTO());

        $presenter = new ManagementHierarchyPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function createBranch(CreateBranchRequest $request)
    {
        $createdItem = $this->managementHierarchyService->createBranch($request->createCreateBranchDTO());

        $presenter = new ManagementHierarchyPresenter($createdItem);

        return Json::item($presenter->getData());
    }


    public function createManagement(CreateManagementRequest $request)
    {
        $createdItem = $this->managementHierarchyService->createManagement($request->createCreateManagementDTO());

        $presenter = new ManagementPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function createDepartment(CreateDepartmentRequest $request)
    {
        $createdItem = $this->managementHierarchyService->createDepartment($request->createCreateDepartmentDTO());

        $presenter = new DepartmentPresenter($createdItem);

        return Json::item($presenter->getData());
    }


    public function makeBranchMain(MakeBranchMainRequest $request)
    {
        $command = $request->createMakeBranchMainCommand();
        $this->makeBranchMainHandler->handle($command);
        $item = $this->managementHierarchyService->get($command->getId());
        $presenter = new ManagementHierarchyPresenter($item);
        return Json::item($presenter->getData());

    }

    public function update(UpdateManagementHierarchyRequest $request): JsonResponse
    {
        $command = $request->createUpdateManagementHierarchyCommand();
        $this->updateManagementHierarchyHandler->handle($command);

        $item = $this->managementHierarchyService->get($command->getId());

        $presenter = new ManagementHierarchyPresenter($item);

        return Json::item($presenter->getData());
    }

    public function updateBranch(UpdateBranchRequest $request): JsonResponse
    {
        $command = $request->createUpdateBranchCommand();
        $this->updateBranchHandler->handle($command);

        $item = $this->managementHierarchyService->get($command->getId());

        $presenter = new ManagementHierarchyPresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteManagementHierarchyRequest $request): JsonResponse
    {
        $this->deleteManagementHierarchyHandler->handle((int)($request->route('id')));

        return Json::deleted();
    }

    public function presentTree(GetManagementHierarchyLookupRequest $request)
    {
        return Json::item(ManagementHierarchyTreePresenter::collection($this->managementHierarchyService->getTree()));
    }

    public function directChildrenTree()
    {
        return Json::item(ManagementHierarchyUserTreePresenter::collection($this->managementHierarchyService->getTree()));
    }


}
