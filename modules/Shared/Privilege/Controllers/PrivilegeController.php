<?php

declare(strict_types=1);

namespace Modules\Shared\Privilege\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\Privilege\Handlers\DeletePrivilegeHandler;
use Modules\Shared\Privilege\Handlers\UpdatePrivilegeHandler;
use Modules\Shared\Privilege\Presenters\PrivilegePresenter;
use Modules\Shared\Privilege\Requests\CreatePrivilegeRequest;
use Modules\Shared\Privilege\Requests\DeletePrivilegeRequest;
use Modules\Shared\Privilege\Requests\GetPrivilegeListRequest;
use Modules\Shared\Privilege\Requests\GetPrivilegeRequest;
use Modules\Shared\Privilege\Requests\UpdatePrivilegeRequest;
use Modules\Shared\Privilege\Services\PrivilegeCRUDService;
use Ramsey\Uuid\Uuid;

class PrivilegeController extends Controller
{
    public function __construct(
        private PrivilegeCRUDService $privilegeService,
        private UpdatePrivilegeHandler $updatePrivilegeHandler,
        private DeletePrivilegeHandler $deletePrivilegeHandler,
    ) {
    }

    public function index(GetPrivilegeListRequest $request): JsonResponse
    {
        $list = $this->privilegeService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(PrivilegePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetPrivilegeRequest $request): JsonResponse
    {
        $item = $this->privilegeService->get(Uuid::fromString($request->route('id')));

        $presenter = new PrivilegePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreatePrivilegeRequest $request): JsonResponse
    {
        $createdItem = $this->privilegeService->create($request->createCreatePrivilegeDTO());

        $presenter = new PrivilegePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdatePrivilegeRequest $request): JsonResponse
    {
        $command = $request->createUpdatePrivilegeCommand();
        $this->updatePrivilegeHandler->handle($command);

        $item = $this->privilegeService->get($command->getId());

        $presenter = new PrivilegePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeletePrivilegeRequest $request): JsonResponse
    {
        $this->deletePrivilegeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
