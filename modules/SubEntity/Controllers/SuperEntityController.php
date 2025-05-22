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
use Modules\SubEntity\Requests\UpdateSuperEntityAttributesConfigRequest;
use Modules\SubEntity\Handlers\UpdateSuperEntityRegistrableConfigHandler;
use Modules\SubEntity\Requests\UpdateSuperEntityRegistrableConfigRequest;
use Modules\SubEntity\Handlers\UpdateSuperEntityRegistrationFormsConfigHandler;
use Modules\SubEntity\Requests\UpdateSuperEntityRegistrationFormsConfigRequest;

class SuperEntityController extends Controller
{
    public function __construct(
        private SuperEntityService $superEntityService,
        private UpdateSuperEntityAttributesConfigHandler $updateSuperEntityAttributesConfigHandler,
        private UpdateSuperEntityRegistrationFormsConfigHandler $updateSuperEntityRegistrationFormsConfigHandler,
        private UpdateSuperEntityRegistrableConfigHandler $updateSuperEntityRegistrableConfigHandler,
    ) {
    }

    public function index(): JsonResponse
    {
        $list = $this->superEntityService->list(request()->get('search'));

        return Json::items(SuperEntityPresenter::collection($list));
    }

    public function getAvailableAttributes(GetSuperEntityAttributesRequest $request): JsonResponse
    {
        $attributes = $this->superEntityService->getAvailableAttributes($request->get('super_entity_id'));

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

        return Json::items($attributes);
    }

    public function setAttributesConfig(UpdateSuperEntityAttributesConfigRequest $request): JsonResponse
    {
        $command = $request->createUpdateSuperEntityAttributesConfigCommand();

        $this->updateSuperEntityAttributesConfigHandler->handle($command);

        $item = $this->superEntityService->getById($command->getId());

        $presenter = new SuperEntityPresenter($item);

        return Json::item($presenter->getData());
    }

    public function setRegistrationFormsConfig(UpdateSuperEntityRegistrationFormsConfigRequest $request): JsonResponse
    {
        $command = $request->createUpdateSuperEntityRegistrationConfigCommand();

        $this->updateSuperEntityRegistrationFormsConfigHandler->handle($command);

        $item = $this->superEntityService->getById($command->getId());

        $presenter = new SuperEntityPresenter($item);

        return Json::item($presenter->getData());
    }

    public function getRegistrationFormsConfig(GetSuperEntityAttributesRequest $request): JsonResponse
    {
        $attributes = $this->superEntityService->getRegistrationFormsConfig($request->get('super_entity_id'));

        return Json::items($attributes['registration_forms']);
    }

    public function setRegistrableConfig(UpdateSuperEntityRegistrableConfigRequest $request): JsonResponse
    {
        $command = $request->createUpdateSuperEntityRegistrationConfigCommand();

        $this->updateSuperEntityRegistrableConfigHandler->handle($command);

        $item = $this->superEntityService->getById($command->getId());

        $presenter = new SuperEntityPresenter($item);

        return Json::item($presenter->getData());
    }

    // TODO: refactor config functionality
    public function getRegistrableConfig(GetSuperEntityAttributesRequest $request): JsonResponse
    {
        $isRegistrable = $this->superEntityService->getIsRegistrableConfig($request->get('super_entity_id'));

        return Json::item(['is_registrable' => $isRegistrable]);
    }

    public function getRegistrationConfig(GetSuperEntityAttributesRequest $request): JsonResponse
    {
        $config = $this->superEntityService->getRegistrationConfig($request->get('super_entity_id'));

        return Json::item($config);
    }
}
