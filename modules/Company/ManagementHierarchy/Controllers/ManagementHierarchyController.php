<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use Modules\Company\ManagementHierarchy\Handlers\DeleteManagementHierarchyHandler;
use Modules\Company\ManagementHierarchy\Handlers\MakeBranchMainHandler;
use Modules\Company\ManagementHierarchy\Handlers\UpdateManagementHierarchyHandler;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\Company\ManagementHierarchy\Requests\CreateBranchRequest;
use Modules\Company\ManagementHierarchy\Requests\CreateManagementHierarchyRequest;
use Modules\Company\ManagementHierarchy\Requests\DeleteManagementHierarchyRequest;
use Modules\Company\ManagementHierarchy\Requests\GetManagementHierarchyListRequest;
use Modules\Company\ManagementHierarchy\Requests\GetManagementHierarchyRequest;
use Modules\Company\ManagementHierarchy\Requests\MakeBranchMainRequest;
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
        private UserRepository $userRepository
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

    public function show(GetManagementHierarchyRequest $request): JsonResponse
    {
        $item = $this->managementHierarchyService->get(Uuid::fromString($request->route('id')));

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

    public function delete(DeleteManagementHierarchyRequest $request): JsonResponse
    {
        $this->deleteManagementHierarchyHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function hierarchies(GetManagementHierarchyListRequest $request)
    {
        $userId = Uuid::fromString($request->route('id'));

        $user = $this->userRepository->getUser($userId);
        $type = $request->get('type', 'branch');
        $list = $this->managementHierarchyService->listCompany(
        Uuid::fromString($user->company_id),
        $type,
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        return Json::items(ManagementHierarchyPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

}
