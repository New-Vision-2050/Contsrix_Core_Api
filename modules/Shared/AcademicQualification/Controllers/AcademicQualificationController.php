<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicQualification\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\AcademicQualification\Handlers\DeleteAcademicQualificationHandler;
use Modules\Shared\AcademicQualification\Handlers\UpdateAcademicQualificationHandler;
use Modules\Shared\AcademicQualification\Presenters\AcademicQualificationPresenter;
use Modules\Shared\AcademicQualification\Requests\CreateAcademicQualificationRequest;
use Modules\Shared\AcademicQualification\Requests\DeleteAcademicQualificationRequest;
use Modules\Shared\AcademicQualification\Requests\GetAcademicQualificationListRequest;
use Modules\Shared\AcademicQualification\Requests\GetAcademicQualificationRequest;
use Modules\Shared\AcademicQualification\Requests\UpdateAcademicQualificationRequest;
use Modules\Shared\AcademicQualification\Services\AcademicQualificationCRUDService;
use Ramsey\Uuid\Uuid;

class AcademicQualificationController extends Controller
{
    public function __construct(
        private AcademicQualificationCRUDService $academicQualificationService,
        private UpdateAcademicQualificationHandler $updateAcademicQualificationHandler,
        private DeleteAcademicQualificationHandler $deleteAcademicQualificationHandler,
    ) {
    }

    public function index(GetAcademicQualificationListRequest $request): JsonResponse
    {
        $list = $this->academicQualificationService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(AcademicQualificationPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetAcademicQualificationRequest $request): JsonResponse
    {
        $item = $this->academicQualificationService->get(Uuid::fromString($request->route('id')));

        $presenter = new AcademicQualificationPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateAcademicQualificationRequest $request): JsonResponse
    {
        $createdItem = $this->academicQualificationService->create($request->createCreateAcademicQualificationDTO());

        $presenter = new AcademicQualificationPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateAcademicQualificationRequest $request): JsonResponse
    {
        $command = $request->createUpdateAcademicQualificationCommand();
        $this->updateAcademicQualificationHandler->handle($command);

        $item = $this->academicQualificationService->get($command->getId());

        $presenter = new AcademicQualificationPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteAcademicQualificationRequest $request): JsonResponse
    {
        $this->deleteAcademicQualificationHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
