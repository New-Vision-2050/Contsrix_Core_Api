<?php

declare(strict_types=1);

namespace Modules\Company\RegistrationType\Controllers;

use BasePackage\Shared\Facade\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Company\RegistrationType\Handlers\DeleteRegistrationTypeHandler;
use Modules\Company\RegistrationType\Handlers\UpdateRegistrationTypeHandler;
use Modules\Company\RegistrationType\Presenters\RegistrationTypePresenter;
use Modules\Company\RegistrationType\Requests\CreateRegistrationTypeRequest;
use Modules\Company\RegistrationType\Requests\DeleteRegistrationTypeRequest;
use Modules\Company\RegistrationType\Requests\GetRegistrationTypeListRequest;
use Modules\Company\RegistrationType\Requests\GetRegistrationTypeRequest;
use Modules\Company\RegistrationType\Requests\UpdateRegistrationTypeRequest;
use Modules\Company\RegistrationType\Services\RegistrationTypeCRUDService;
use Ramsey\Uuid\Uuid;

class RegistrationTypeController extends Controller
{
    public function __construct(
        private RegistrationTypeCRUDService $RegistrationTypeService,
        private UpdateRegistrationTypeHandler $updateRegistrationTypeHandler,
        private DeleteRegistrationTypeHandler $deleteRegistrationTypeHandler,
    ) {
    }

    public function index(GetRegistrationTypeListRequest $request): JsonResponse
    {
        $list = $this->RegistrationTypeService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::buildItems(null,['RegistrationTypes' => RegistrationTypePresenter::collection($list['data']),'pagination' => $list['pagination']]);
    }

    public function show(GetRegistrationTypeRequest $request): JsonResponse
    {
        $item = $this->RegistrationTypeService->get(Uuid::fromString($request->route('id')));

        $presenter = new RegistrationTypePresenter($item);

        return Json::buildItems('RegistrationType', $presenter->getData());
    }

    public function store(CreateRegistrationTypeRequest $request): JsonResponse
    {
        $createdItem = $this->RegistrationTypeService->create($request->createCreateRegistrationTypeDTO());

        $presenter = new RegistrationTypePresenter($createdItem);

        return Json::buildItems('RegistrationType', $presenter->getData());
    }

    public function update(UpdateRegistrationTypeRequest $request): JsonResponse
    {
        $command = $request->createUpdateRegistrationTypeCommand();
        $this->updateRegistrationTypeHandler->handle($command);

        $item = $this->RegistrationTypeService->get($command->getId());

        $presenter = new RegistrationTypePresenter($item);

        return Json::buildItems('RegistrationType', $presenter->getData());
    }

    public function delete(DeleteRegistrationTypeRequest $request): JsonResponse
    {
        $this->deleteRegistrationTypeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
