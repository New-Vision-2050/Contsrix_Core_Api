<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;
use Modules\Company\ManagementHierarchy\Handlers\DeleteManagementHierarchyHandler;
use Modules\Company\ManagementHierarchy\Handlers\MakeBranchMainHandler;
use Modules\Company\ManagementHierarchy\Handlers\UpdateBranchHandler;
use Modules\Company\ManagementHierarchy\Handlers\UpdateManagementHandler;
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
use Modules\Company\ManagementHierarchy\Requests\UpdateManagementRequest;
use Modules\Company\ManagementHierarchy\Services\ManagementHierarchyCRUDService;
use Modules\Shared\Currency\Requests\GetUsersLowLevelRequest;
use Modules\User\Models\User;
use Modules\User\Presenters\UserPresenter;
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
        private UpdateManagementHandler          $updateManagementHandler
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

    public function listWithoutPagination(GetManagementHierarchyLookupRequest $request)
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
        try {
            $this->deleteManagementHierarchyHandler->handle((int)($request->route('id')));
            return Json::deleted();
        } catch (Exception $e) {
            if ($e->getCode() === 422) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            // For any other exceptions, rethrow
            throw $e;
        }
    }

    public function updateManagement(UpdateManagementRequest $request): JsonResponse
    {
        $command = $request->createUpdateManagementCommand();
        $this->updateManagementHandler->handle($command);

        $item = $this->managementHierarchyService->get($command->getId());

        $presenter = new ManagementPresenter($item);

        return Json::item($presenter->getData());
    }

    /**
     * Consolidate multiple tree nodes by making all nodes children of the node with lowest ID
     *
     * @param array $treeNodes Array of tree nodes to consolidate
     * @return array Consolidated tree with nodes attached to the lowest ID node
     */
    private function consolidateTreeNodesUnderLowestId(array $treeNodes): array
    {
        if (count($treeNodes) <= 1) {
            return $treeNodes;
        }

        // Find the node with the lowest ID to use as the root
        $lowestIdIndex = 0;
        $lowestId = $treeNodes[0]['id'];

        for ($i = 1; $i < count($treeNodes); $i++) {
            if ($treeNodes[$i]['id'] < $lowestId) {
                $lowestId = $treeNodes[$i]['id'];
                $lowestIdIndex = $i;
            }
        }

        // Node with lowest ID will be our root node
        $rootNode = $treeNodes[$lowestIdIndex];

        // Start with existing children of the root node or empty array
        $allChildren = isset($rootNode['children']) ? $rootNode['children'] : [];

        // Add all other nodes as children of the root node
        for ($i = 0; $i < count($treeNodes); $i++) {
            if ($i !== $lowestIdIndex) {
                $allChildren[] = $treeNodes[$i];
            }
        }

        // Update the count properties for the root node
        $rootNode['department_count'] = array_sum(array_column($treeNodes, 'department_count'));
        $rootNode['management_count'] = array_sum(array_column($treeNodes, 'management_count'));
        $rootNode['branch_count'] = array_sum(array_column($treeNodes, 'branch_count'));
        $rootNode['user_count'] = array_sum(array_column($treeNodes, 'user_count'));

        // Set the consolidated children to the root node
        $rootNode['children'] = $allChildren;

        return $rootNode;
    }

    /**
     * Present the management hierarchy tree with optional filtering
     *
     * @param GetManagementHierarchyLookupRequest $request
     * @return JsonResponse
     */
    public function presentTree(GetManagementHierarchyLookupRequest $request)
    {
        $type = $request->input('type');

        if ($type == "management") {
            // When type is management:
            // 1. Don't skip management nodes with is_main=1
            // 2. Skip branch nodes entirely
            ManagementHierarchyTreePresenter::setSkipManagementMainNodes(false);
            ManagementHierarchyTreePresenter::setSkipBranchNodes(true);
        } else {
            // For other types or no type specified:
            // 1. Skip management nodes with is_main=1
            // 2. Don't skip branch nodes
            ManagementHierarchyTreePresenter::setSkipManagementMainNodes(true);
            ManagementHierarchyTreePresenter::setSkipBranchNodes(false);
        }

        $tree = $this->managementHierarchyService->getTree();

        if ($type == "management") {
            $presentedTree = ManagementHierarchyTreePresenter::collection($tree);

            // If we have multiple top-level nodes, consolidate them under the node with lowest ID
            if (is_array($presentedTree[0]) && count($presentedTree[0]) > 1) {
                $presentedTree = $this->consolidateTreeNodesUnderLowestId($presentedTree[0]);
            } else if (is_array($presentedTree[0]) && count($presentedTree[0]) == 1) {
                $presentedTree = $presentedTree[0];
            }
        } else {
            $presentedTree = ManagementHierarchyTreePresenter::collection($tree);
        }

        return Json::item($presentedTree);
    }

    public function directChildrenTree()
    {
        return Json::item(ManagementHierarchyUserTreePresenter::collection($this->managementHierarchyService->getTree()));
    }

    /**
     * Get all lower level users in the management hierarchy tree for a specific user
     *
     * @param GetUsersLowLevelRequest $request
     * @return JsonResponse
     */
    public function getUserLowerLevels(GetUsersLowLevelRequest $request)
    {
        try {
            $userId = uuid::fromString($request->input('user_id'));
            $lowerUsers = $this->managementHierarchyService->getLowerUsers($userId);

            return Json::items(
                UserPresenter::collection($lowerUsers)
            );
        } catch (Exception $e) {
            return Json::error($e->getMessage(),400);
        }
    }

}
