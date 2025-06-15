<?php

declare(strict_types=1);

namespace Modules\SubEntity\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\SubEntity\Services\SuperEntityService;
use Modules\SubEntity\Presenters\SuperEntityPresenter;
use Modules\SubEntity\Presenters\RegistrationFormPresenter;
use Modules\SubEntity\Requests\GetSuperEntityAttributesRequest;
use Modules\SubEntity\Requests\GetSuperEntityRegistrationFormsRequest;
use Modules\SubEntity\Handlers\UpdateSuperEntityAttributesConfigHandler;
use Modules\SubEntity\Presenters\SuperEntityRegistrationConfigPresenter;
use Modules\SubEntity\Requests\UpdateSuperEntityAttributesConfigRequest;
use Modules\SubEntity\Handlers\UpdateSuperEntityRegistrationConfigHandler;
use Modules\SubEntity\Requests\UpdateSuperEntityRegistrationConfigRequest;
use Modules\SubEntity\Presenters\SuperEntityAttributesConfigPresenter;

class SuperEntityController extends Controller
{
    public function __construct(
        private SuperEntityService $superEntityService,
        private UpdateSuperEntityAttributesConfigHandler $updateSuperEntityAttributesConfigHandler,
        private UpdateSuperEntityRegistrationConfigHandler $updateSuperEntityRegistrationConfigHandler,
    ) {
    }

    public function index(): JsonResponse
    {
        $list = $this->superEntityService->list(request()->get('search'));

        return Json::items(SuperEntityPresenter::collection($list));
    }

    public function getDefaultAttributes(GetSuperEntityAttributesRequest $request): JsonResponse
    {
        $attributes = $this->superEntityService->getDefaultAttributes($request->get('super_entity_id'));

        return Json::items($attributes);
    }

     public function getOptionalAttributes(GetSuperEntityAttributesRequest $request): JsonResponse
    {
        $attributes = $this->superEntityService->getOptionalAttributes($request->get('super_entity_id'));

        return Json::items($attributes);
    }

    public function getAllAttributesForSelection(GetSuperEntityAttributesRequest $request): JsonResponse
    {
        $attributes = $this->superEntityService->getAllAttributesForSelection($request->get('super_entity_id'));

        return Json::items($attributes);
    }

    public function getRegistrationForms(GetSuperEntityRegistrationFormsRequest $request): JsonResponse
    {
        $forms = $this->superEntityService->getRegistrationFormsForId($request->get('super_entity_id'));

        return Json::items(RegistrationFormPresenter::collection($forms));
    }

    public function getAttributesConfig(GetSuperEntityAttributesRequest $request): JsonResponse
    {
        $attributes = $this->superEntityService->getAttributesConfig($request->get('super_entity_id'));

        return Json::item($attributes);
    }

    public function setAttributesConfig(UpdateSuperEntityAttributesConfigRequest $request): JsonResponse
    {
        $command = $request->createUpdateSuperEntityAttributesConfigCommand();

        $this->updateSuperEntityAttributesConfigHandler->handle($command);

        $item = $this->superEntityService->getById($command->getId());

        $presenter = new SuperEntityAttributesConfigPresenter($item);

        return Json::item($presenter->getData());
    }

    public function setRegistrationConfig(UpdateSuperEntityRegistrationConfigRequest $request): JsonResponse
    {
        $command = $request->createUpdateSuperEntityRegistrationConfigCommand();

        $this->updateSuperEntityRegistrationConfigHandler->handle($command);

        $item = $this->superEntityService->getRegistrationConfig($command->getId());

        $presenter = new SuperEntityRegistrationConfigPresenter($item);

        return Json::item($presenter->getData());
    }
    public function getRegistrationConfig(GetSuperEntityAttributesRequest $request): JsonResponse
    {
        $config = $this->superEntityService->getRegistrationConfig($request->get('super_entity_id'));

        return Json::item($config);
    }
}
