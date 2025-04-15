<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\AcademicSpecialization\Handlers\DeleteAcademicSpecializationHandler;
use Modules\Shared\AcademicSpecialization\Handlers\UpdateAcademicSpecializationHandler;
use Modules\Shared\AcademicSpecialization\Presenters\AcademicSpecializationPresenter;
use Modules\Shared\AcademicSpecialization\Requests\CreateAcademicSpecializationRequest;
use Modules\Shared\AcademicSpecialization\Requests\DeleteAcademicSpecializationRequest;
use Modules\Shared\AcademicSpecialization\Requests\GetAcademicSpecializationListRequest;
use Modules\Shared\AcademicSpecialization\Requests\GetAcademicSpecializationRequest;
use Modules\Shared\AcademicSpecialization\Requests\UpdateAcademicSpecializationRequest;
use Modules\Shared\AcademicSpecialization\Services\AcademicSpecializationCRUDService;
use Ramsey\Uuid\Uuid;

class AcademicSpecializationController extends Controller
{
    public function __construct(
        private AcademicSpecializationCRUDService $academicSpecializationService,
        private UpdateAcademicSpecializationHandler $updateAcademicSpecializationHandler,
        private DeleteAcademicSpecializationHandler $deleteAcademicSpecializationHandler,
    ) {
    }

    public function index(GetAcademicSpecializationListRequest $request): JsonResponse
    {
        $list = $this->academicSpecializationService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(AcademicSpecializationPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetAcademicSpecializationRequest $request): JsonResponse
    {
        $item = $this->academicSpecializationService->get(Uuid::fromString($request->route('id')));

        $presenter = new AcademicSpecializationPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateAcademicSpecializationRequest $request): JsonResponse
    {
        $createdItem = $this->academicSpecializationService->create($request->createCreateAcademicSpecializationDTO());

        $presenter = new AcademicSpecializationPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateAcademicSpecializationRequest $request): JsonResponse
    {
        $command = $request->createUpdateAcademicSpecializationCommand();
        $this->updateAcademicSpecializationHandler->handle($command);

        $item = $this->academicSpecializationService->get($command->getId());

        $presenter = new AcademicSpecializationPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteAcademicSpecializationRequest $request): JsonResponse
    {
        $this->deleteAcademicSpecializationHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
