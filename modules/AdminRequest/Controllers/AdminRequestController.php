<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\AdminRequest\Handlers\DeleteAdminRequestHandler;
use Modules\AdminRequest\Handlers\TakeActionAdminRequestHandler;
use Modules\AdminRequest\Presenters\AdminRequestPresenter;
use Modules\AdminRequest\Requests\CreateAdminRequestRequest;
use Modules\AdminRequest\Requests\DeleteAdminRequestRequest;
use Modules\AdminRequest\Requests\GetAdminRequestListRequest;
use Modules\AdminRequest\Requests\GetAdminRequestRequest;
use Modules\AdminRequest\Requests\TakeActionOnAdminRequestRequest;
use Modules\AdminRequest\Requests\UpdateAdminRequestRequest;
use Modules\AdminRequest\Services\AdminRequestCRUDService;
use Ramsey\Uuid\Uuid;

class AdminRequestController extends Controller
{
    public function __construct(
        private AdminRequestCRUDService       $adminRequestService,
        private TakeActionAdminRequestHandler $actionAdminRequestHandler,
        private DeleteAdminRequestHandler     $deleteAdminRequestHandler,
    )
    {
    }

    public function index(GetAdminRequestListRequest $request): JsonResponse
    {
        $list = $this->adminRequestService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        return Json::items(AdminRequestPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetAdminRequestRequest $request): JsonResponse
    {
        $item = $this->adminRequestService->get(Uuid::fromString($request->route('id')));

        $presenter = new AdminRequestPresenter($item);

        return Json::item($presenter->getData());
    }

    public function takeActionRequest(TakeActionOnAdminRequestRequest $request): JsonResponse
    {
        $command = $request->createUpdateAdminRequestCommand();
        try {
            $this->actionAdminRequestHandler->handle($command);

            $item = $this->adminRequestService->get($command->getId());

            $presenter = new AdminRequestPresenter($item);
        }
        catch (\Exception $exception) {
            return Json::error($exception->getMessage(), httpStatus: $exception->getCode());
        }


        return Json::item($presenter->getData());
    }


    public function delete(DeleteAdminRequestRequest $request): JsonResponse
    {
        $this->deleteAdminRequestHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
