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

class SuperEntityController extends Controller
{
    public function __construct(
        private SuperEntityService $superEntityService,
        private UpdateSuperEntityAttributesConfigHandler $updateSuperEntityAttributesConfigHandler,
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

    public function setAttributesConfig(UpdateSuperEntityAttributesConfigRequest $request): JsonResponse{
        $command = $request->createUpdateSuperEntityAttributesConfigCommand();

        $this->updateSuperEntityAttributesConfigHandler->handle($command);

        $item = $this->superEntityService->getById($command->getId());

        $presenter = new SuperEntityPresenter($item);

        return Json::item($presenter->getData());
    }
}
