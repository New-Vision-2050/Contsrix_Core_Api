<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\MedicalInsurance\Handlers\DeleteMedicalInsuranceHandler;
use Modules\MedicalInsurance\Handlers\UpdateMedicalInsuranceHandler;
use Modules\MedicalInsurance\Presenters\MedicalInsurancePresenter;
use Modules\MedicalInsurance\Requests\CreateMedicalInsuranceRequest;
use Modules\MedicalInsurance\Requests\DeleteMedicalInsuranceRequest;
use Modules\MedicalInsurance\Requests\GetMedicalInsuranceListRequest;
use Modules\MedicalInsurance\Requests\GetMedicalInsuranceRequest;
use Modules\MedicalInsurance\Requests\UpdateMedicalInsuranceRequest;
use Modules\MedicalInsurance\Services\MedicalInsuranceCRUDService;
use Modules\MedicalInsurance\Exports\MedicalInsuranceExport;
use Modules\MedicalInsurance\Requests\ExportMedicalInsuranceRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class MedicalInsuranceController extends Controller
{
    public function __construct(
        private MedicalInsuranceCRUDService $medicalInsuranceService,
        private UpdateMedicalInsuranceHandler $updateMedicalInsuranceHandler,
        private DeleteMedicalInsuranceHandler $deleteMedicalInsuranceHandler,
    ) {
    }

    public function index(GetMedicalInsuranceListRequest $request): JsonResponse
    {
        $list = $this->medicalInsuranceService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(MedicalInsurancePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetMedicalInsuranceRequest $request): JsonResponse
    {
        $item = $this->medicalInsuranceService->get(Uuid::fromString($request->route('id')));

        $presenter = new MedicalInsurancePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateMedicalInsuranceRequest $request): JsonResponse
    {
        $createdItem = $this->medicalInsuranceService->create($request->createCreateMedicalInsuranceDTO());

        $presenter = new MedicalInsurancePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateMedicalInsuranceRequest $request): JsonResponse
    {
        $command = $request->createUpdateMedicalInsuranceCommand();
        $this->updateMedicalInsuranceHandler->handle($command);

        $item = $this->medicalInsuranceService->get($command->getId());

        $presenter = new MedicalInsurancePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteMedicalInsuranceRequest $request): JsonResponse
    {
        $this->deleteMedicalInsuranceHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export medicalinsurance to a file
     *
     * @param ExportMedicalInsuranceRequest $request
     */
    public function export(ExportMedicalInsuranceRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'medical_insurance.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new MedicalInsuranceExport($this->medicalInsuranceService, $filters), $fileName);
    }
}
