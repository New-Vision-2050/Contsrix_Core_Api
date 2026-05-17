<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\MedicalInsurance\Handlers\DeleteMedicalInsuranceCategoryHandler;
use Modules\MedicalInsurance\Handlers\UpdateMedicalInsuranceCategoryHandler;
use Modules\MedicalInsurance\Presenters\MedicalInsuranceCategoryPresenter;
use Modules\MedicalInsurance\Requests\CreateMedicalInsuranceCategoryRequest;
use Modules\MedicalInsurance\Requests\DeleteMedicalInsuranceCategoryRequest;
use Modules\MedicalInsurance\Requests\GetMedicalInsuranceCategoryListRequest;
use Modules\MedicalInsurance\Requests\GetMedicalInsuranceCategoryRequest;
use Modules\MedicalInsurance\Requests\UpdateMedicalInsuranceCategoryRequest;
use Modules\MedicalInsurance\Services\MedicalInsuranceCategoryCRUDService;
use Ramsey\Uuid\Uuid;

class MedicalInsuranceCategoryController extends Controller
{
    public function __construct(
        private MedicalInsuranceCategoryCRUDService $categoryService,
        private UpdateMedicalInsuranceCategoryHandler $updateHandler,
        private DeleteMedicalInsuranceCategoryHandler $deleteHandler,
    ) {
    }

    public function index(GetMedicalInsuranceCategoryListRequest $request): JsonResponse
    {
        $list = $this->categoryService->list(
            medicalInsuranceId: $request->route('id'),
            page: (int) $request->get('page', 1),
            perPage: (int) $request->get('per_page', 10),
        );

        return Json::items(
            MedicalInsuranceCategoryPresenter::collection($list['data']),
            paginationSettings: $list['pagination']
        );
    }

    public function show(GetMedicalInsuranceCategoryRequest $request): JsonResponse
    {
        $item = $this->categoryService->get(Uuid::fromString($request->route('category_id')));

        return Json::item((new MedicalInsuranceCategoryPresenter($item))->getData());
    }

    public function store(CreateMedicalInsuranceCategoryRequest $request): JsonResponse
    {
        $item = $this->categoryService->create($request->createDTO());

        return Json::item((new MedicalInsuranceCategoryPresenter($item))->getData());
    }

    public function update(UpdateMedicalInsuranceCategoryRequest $request): JsonResponse
    {
        $command = $request->createCommand();
        $this->updateHandler->handle($command);

        $item = $this->categoryService->get($command->getId());

        return Json::item((new MedicalInsuranceCategoryPresenter($item))->getData());
    }

    public function delete(DeleteMedicalInsuranceCategoryRequest $request): JsonResponse
    {
        $this->deleteHandler->handle(Uuid::fromString($request->route('category_id')));

        return Json::deleted();
    }
}
